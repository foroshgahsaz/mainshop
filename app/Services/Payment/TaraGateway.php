<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TaraGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->tara();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه اعتباری تارا فعال نیست.');
        }

        if ($config['username'] === '' || $config['password'] === '' || $config['service_id'] === '') {
            throw new RuntimeException('تنظیمات درگاه تارا ناقص است.');
        }

        $order->loadMissing(['user', 'items']);

        $mobile = $this->normalizeMobile((string) ($order->user?->phone ?? ''));
        if ($mobile === '') {
            throw new RuntimeException('شماره موبایل مشتری برای پرداخت تارا الزامی است.');
        }

        $accessToken = $this->accessToken($config);
        $gatewayAmount = AmountConverter::toGateway($payment->amount, $config['amount_unit']);
        $callbackUrl = url($config['callback_url']).'?payment='.$payment->tracking_code;
        $ip = $this->clientIp($config);

        $payload = [
            'ip' => $ip,
            'serviceAmountList' => [[
                'serviceId' => (int) $config['service_id'],
                'amount' => $gatewayAmount,
            ]],
            'taraInvoiceItemList' => [$this->invoiceItem($order, $payment, $config, $gatewayAmount)],
            'additionalData' => $payment->tracking_code,
            'callBackUrl' => $callbackUrl,
            'amount' => (string) $gatewayAmount,
            'mobile' => $mobile,
            'orderId' => $payment->tracking_code,
            'vat' => 0,
        ];

        $response = $this->http($config['base_url'])
            ->withToken($accessToken, 'bearer')
            ->post('/api/getToken', $payload);

        $data = $this->json($response);
        $token = (string) data_get($data, 'token', data_get($data, 'data.token', ''));
        $result = data_get($data, 'result', data_get($data, 'data.result'));

        if ($response->failed() || $token === '' || ($result !== null && ! $this->isOk($result))) {
            $previous = $payment->status;
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'raw_response' => $data,
            ]);

            $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, 'خطا در دریافت توکن تارا');
            $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);

            throw new RuntimeException($this->errorMessage($data, 'خطا در اتصال به درگاه تارا.'));
        }

        $payment->update([
            'transaction_id' => $token,
            'raw_response' => $data,
        ]);

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'توکن تارا دریافت شد و کاربر به درگاه اعتباری هدایت می‌شود.',
            ['token' => $token]
        );

        return route('payment.tara.redirect', $payment->tracking_code);
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        $config = $this->settings->tara();
        $token = $authority !== '' ? $authority : (string) $payment->transaction_id;

        if ($token === '') {
            return GatewayVerificationResult::failed(['status' => $status], 'توکن تارا یافت نشد');
        }

        if (! $this->isOk($status) && $status !== '') {
            return GatewayVerificationResult::canceled(
                ['result' => $status, 'token' => $token],
                'انصراف یا ناموفق بودن پرداخت تارا'
            );
        }

        $verified = $this->purchaseVerify($config, $token);

        if (! $this->isOk(data_get($verified, 'result', data_get($verified, 'data.result')))) {
            $verified = $this->purchaseInquiry($config, $token);
        }

        $result = data_get($verified, 'result', data_get($verified, 'data.result'));
        $rrn = (string) data_get($verified, 'rrn', data_get($verified, 'data.rrn', ''));
        $gatewayAmount = data_get($verified, 'amount', data_get($verified, 'data.amount'));

        if (! $this->isOk($result) || $gatewayAmount === null || $gatewayAmount === '') {
            return GatewayVerificationResult::failed($verified, $this->errorMessage($verified, 'تأیید پرداخت تارا ناموفق بود'));
        }

        $paidAmount = AmountConverter::fromGateway($gatewayAmount, $config['amount_unit']);

        if ($paidAmount <= 0) {
            return GatewayVerificationResult::failed($verified, 'مبلغ تأییدشده تارا نامعتبر است');
        }

        return GatewayVerificationResult::success(
            $rrn !== '' ? $rrn : null,
            $verified,
            min($paidAmount, $payment->amount),
        );
    }

    /** @param  array<string, mixed>  $config */
    public function purchaseAction(array $config): string
    {
        return rtrim((string) $config['base_url'], '/').'/api/ipgPurchase';
    }

    /** @param  array<string, mixed>  $config */
    protected function accessToken(array $config): string
    {
        $cacheKey = 'tara:access_token:'.sha1($config['base_url'].'|'.$config['username']);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->http($config['base_url'])->post('/api/v2/authenticate', [
            'username' => $config['username'],
            'password' => $config['password'],
        ]);

        $data = $this->json($response);
        $token = (string) data_get($data, 'accessToken', data_get($data, 'data.accessToken', ''));

        if ($response->failed() || $token === '') {
            throw new RuntimeException($this->errorMessage($data, 'ورود به درگاه تارا ناموفق بود.'));
        }

        $ttl = $this->tokenTtl(data_get($data, 'expireTime', data_get($data, 'data.expireTime')));
        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /** @param  array<string, mixed>  $config */
    protected function purchaseVerify(array $config, string $token): array
    {
        $response = $this->http($config['base_url'])
            ->withToken($this->accessToken($config), 'bearer')
            ->post('/api/purchaseVerify', [
                'ip' => $this->clientIp($config),
                'token' => $token,
            ]);

        return $this->json($response);
    }

    /** @param  array<string, mixed>  $config */
    protected function purchaseInquiry(array $config, string $token): array
    {
        $response = $this->http($config['base_url'])
            ->withToken($this->accessToken($config), 'bearer')
            ->post('/api/purchaseInquiry', [
                'ip' => $this->clientIp($config),
                'token' => $token,
            ]);

        $data = $this->json($response);
        $track = data_get($data, 'trackPurchaseList.0', data_get($data, 'data.trackPurchaseList.0'));

        if (is_array($track)) {
            return array_merge($data, $track);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function invoiceItem(Order $order, Payment $payment, array $config, int $gatewayAmount): array
    {
        return [
            'name' => 'سفارش '.$order->tracking_code,
            'code' => $payment->tracking_code,
            'count' => 1,
            'unit' => 5,
            'fee' => $gatewayAmount,
            'group' => (string) $config['default_group'],
            'groupTitle' => (string) $config['default_group_title'],
            'data' => $order->tracking_code,
        ];
    }

    /** @param  array<string, mixed>  $config */
    protected function clientIp(array $config): string
    {
        $configured = trim((string) ($config['client_ip'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        return request()->ip() ?: '127.0.0.1';
    }

    protected function http(string $baseUrl): PendingRequest
    {
        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /** @return array<string, mixed> */
    protected function json(Response $response): array
    {
        $data = $response->json();

        return is_array($data) ? $data : ['body' => $response->body(), 'status' => $response->status()];
    }

    protected function isOk(mixed $result): bool
    {
        return $result === 0 || $result === '0';
    }

    /** @param  array<string, mixed>  $data */
    protected function errorMessage(array $data, string $fallback): string
    {
        $message = data_get($data, 'description', data_get($data, 'data.description', data_get($data, 'message')));

        return is_string($message) && $message !== '' ? $message : $fallback;
    }

    protected function tokenTtl(mixed $expireTime): int
    {
        $expire = (int) $expireTime;

        if ($expire > 1_000_000_000_000) {
            $expire = (int) floor($expire / 1000);
        }

        if ($expire > time()) {
            return max(60, $expire - time() - 30);
        }

        return 25 * 60;
    }

    protected function normalizeMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
