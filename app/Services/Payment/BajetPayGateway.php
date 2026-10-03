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

class BajetPayGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->bajet();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه باجت‌پی فعال نیست.');
        }

        $this->assertCredentials($config);

        $order->loadMissing(['user', 'items.product.brand']);

        $mobile = $this->normalizeMobile((string) ($order->user?->phone ?? ''));
        if ($mobile === '') {
            throw new RuntimeException('شماره موبایل مشتری برای پرداخت باجت‌پی الزامی است.');
        }

        $gatewayAmount = AmountConverter::toGateway($payment->amount, $config['amount_unit']);
        $returnUrl = url($config['callback_url']).'?payment='.$payment->tracking_code;

        $payload = [
            'orderId' => $payment->tracking_code,
            'amount' => $gatewayAmount,
            'mobile' => $mobile,
            'returnUrl' => $returnUrl,
            'basketItems' => $this->basketItems($order, $config),
        ];

        $nationalId = $this->normalizeNationalId((string) ($order->user?->national_code ?? ''));
        if ($nationalId !== '') {
            $payload['nationalId'] = $nationalId;
        }

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'bajet_create_order',
            'درخواست ایجاد سفارش به باجت‌پی ارسال شد.',
            ['amount' => $gatewayAmount, 'order_id' => $payment->tracking_code]
        );

        $response = $this->http($config)
            ->withToken($this->accessToken($config), 'Bearer')
            ->post('/api/v1/jetpay/order', $payload);

        $data = $this->json($response);
        $referUrl = (string) data_get($data, 'result.referUrl', '');
        $referenceId = (string) data_get($data, 'result.referenceId', '');
        $success = (bool) data_get($data, 'success', false);

        if ($response->failed() || ! $success || $referUrl === '' || $referenceId === '') {
            $previous = $payment->status;
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'raw_response' => $data,
            ]);

            $this->audit->failure(
                $payment->fresh(),
                PaymentAuditStep::GATEWAY_RESPONSE,
                'bajet_order_failed',
                'ایجاد سفارش باجت‌پی ناموفق بود.',
                null,
                ['http_status' => $response->status()]
            );

            $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, $this->errorMessage($data, 'خطا در اتصال به باجت‌پی'));
            $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);

            throw new RuntimeException($this->errorMessage($data, 'خطا در اتصال به درگاه باجت‌پی.'));
        }

        $payment->update([
            'transaction_id' => $referenceId,
            'raw_response' => $data,
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'bajet_order_created',
            'سفارش باجت‌پی ایجاد شد.',
            ['reference_id' => $referenceId]
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به پرتال باجت‌پی (جت‌پی) هدایت می‌شود.',
            ['referenceId' => $referenceId, 'referUrl' => $referUrl]
        );

        return $referUrl;
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        $config = $this->settings->bajet();
        $referenceId = $authority !== '' ? $authority : (string) $payment->transaction_id;

        if ($referenceId === '') {
            return GatewayVerificationResult::failed(['status' => $status], 'شناسه تراکنش باجت‌پی یافت نشد');
        }

        if ($this->isCanceledStatus($status)) {
            return GatewayVerificationResult::canceled(
                ['status' => $status, 'referenceId' => $referenceId],
                'انصراف یا ناموفق بودن پرداخت باجت‌پی'
            );
        }

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'bajet_verify_request',
            'درخواست تأیید نهایی باجت‌پی ارسال شد.',
            ['referenceId' => $referenceId]
        );

        $response = $this->http($config)
            ->withToken($this->accessToken($config), 'Bearer')
            ->post('/api/v1/jetpay/verify', [
                'referenceId' => $referenceId,
            ]);

        $data = $this->json($response);
        $success = (bool) data_get($data, 'success', false);

        if ($response->failed() || ! $success) {
            $inquiry = $this->inquiry($config, $referenceId);
            $finalStatus = (string) data_get($inquiry, 'result.finalStatus', '');
            if ($finalStatus === 'VERIFIED' || $finalStatus === 'SUCCESS') {
                return $this->verificationFromInquiry($inquiry, $config);
            }

            return GatewayVerificationResult::failed($data, $this->errorMessage($data, 'تأیید نهایی باجت‌پی ناموفق بود'));
        }

        return $this->verificationFromVerifyPayload($data, $config);
    }

    /** @param  array<string, mixed>  $config */
    public function inquiry(array $config, string $referenceId): array
    {
        $response = $this->http($config)
            ->withToken($this->accessToken($config), 'Bearer')
            ->post('/api/v1/jetpay/inquiry', [
                'referenceId' => $referenceId,
            ]);

        return $this->json($response);
    }

    /** @param  array<string, mixed>  $config */
    public function accessToken(array $config): string
    {
        $cacheKey = 'bajet:access_token:'.sha1($config['base_url'].'|'.$config['username'].'|'.$config['terminal_id']);

        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->http($config)->post('/api/v1/jetpay/token', [
            'username' => $config['username'],
            'password' => $config['password'],
            'terminalId' => $config['terminal_id'],
        ]);

        $data = $this->json($response);
        $token = (string) data_get($data, 'result.token', '');
        $success = (bool) data_get($data, 'success', false);

        if ($response->failed() || ! $success || $token === '') {
            throw new RuntimeException($this->errorMessage($data, 'ورود به باجت‌پی ناموفق بود.'));
        }

        $expiresIn = (int) data_get($data, 'result.expiresIn', 3600);
        Cache::put($cacheKey, $token, max(60, $expiresIn - 60));

        return $token;
    }

    /** @param  array<string, mixed>  $config */
    protected function verificationFromVerifyPayload(array $data, array $config): GatewayVerificationResult
    {
        $credit = (int) data_get($data, 'result.creditAmount', 0);
        $cash = (int) data_get($data, 'result.cashAmount', 0);
        $gatewayTotal = $credit + $cash;

        if ($gatewayTotal <= 0) {
            return GatewayVerificationResult::failed($data, 'مبلغ تأییدشده باجت‌پی نامعتبر است');
        }

        $paidAmount = AmountConverter::fromGateway($gatewayTotal, $config['amount_unit']);
        $referenceId = (string) data_get($data, 'result.referenceId', '');

        return GatewayVerificationResult::success(
            $referenceId !== '' ? $referenceId : null,
            $data,
            $paidAmount,
        );
    }

    /** @param  array<string, mixed>  $config */
    protected function verificationFromInquiry(array $data, array $config): GatewayVerificationResult
    {
        $credit = (int) data_get($data, 'result.creditAmount', 0);
        $cash = (int) data_get($data, 'result.cashAmount', 0);
        $gatewayTotal = $credit + $cash;

        if ($gatewayTotal <= 0) {
            $amount = (int) data_get($data, 'result.amount', 0);
            $gatewayTotal = $amount;
        }

        if ($gatewayTotal <= 0) {
            return GatewayVerificationResult::failed($data, 'مبلغ استعلام باجت‌پی نامعتبر است');
        }

        $paidAmount = AmountConverter::fromGateway($gatewayTotal, $config['amount_unit']);

        return GatewayVerificationResult::success(
            (string) data_get($data, 'result.referenceId', ''),
            $data,
            $paidAmount,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{brand: string, productType: int, count: int}>
     */
    protected function basketItems(Order $order, array $config): array
    {
        $defaultType = (int) ($config['default_product_type'] ?? 2);
        $defaultBrand = (string) ($config['default_brand'] ?? 'general');
        $items = [];

        foreach ($order->items as $item) {
            $brand = $item->product?->brand?->name
                ?: $defaultBrand;
            $items[] = [
                'brand' => mb_substr($brand, 0, 64),
                'productType' => $defaultType,
                'count' => max(1, (int) $item->quantity),
            ];
        }

        if ($items === []) {
            $items[] = [
                'brand' => $defaultBrand,
                'productType' => $defaultType,
                'count' => 1,
            ];
        }

        return $items;
    }

    /** @param  array<string, mixed>  $config */
    protected function http(array $config): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $config['base_url'], '/'))
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

    /** @param  array<string, mixed>  $data */
    protected function errorMessage(array $data, string $fallback): string
    {
        $fa = data_get($data, 'result.error.fa');
        if (is_string($fa) && $fa !== '') {
            return $fa;
        }

        $en = data_get($data, 'result.error.en', data_get($data, 'message'));
        if (is_string($en) && $en !== '') {
            return $en;
        }

        return $fallback;
    }

    /** @param  array<string, mixed>  $config */
    protected function assertCredentials(array $config): void
    {
        if ($config['username'] === '' || $config['password'] === '' || $config['terminal_id'] === '' || $config['base_url'] === '') {
            throw new RuntimeException('تنظیمات درگاه باجت‌پی ناقص است.');
        }
    }

    protected function isCanceledStatus(string $status): bool
    {
        $normalized = strtolower(trim($status));

        return in_array($normalized, ['false', '0', 'nok', 'cancel', 'canceled', 'failed'], true);
    }

    protected function normalizeMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0'.$digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : '';
    }

    protected function normalizeNationalId(string $code): string
    {
        $digits = preg_replace('/\D+/', '', $code) ?? '';

        return strlen($digits) === 10 ? $digits : '';
    }
}
