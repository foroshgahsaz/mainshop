<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SnappPayGateway implements PaymentGatewayInterface
{
    private const PAYMENT_METHOD_INSTALLMENT = 'INSTALLMENT';

    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->snapppay();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه اسنپ‌پی فعال نیست.');
        }

        $this->assertCredentials($config);

        $order->loadMissing(['user', 'items.product.category']);

        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];
        $attempt = (int) ($raw['snapppay_attempt'] ?? 0) + 1;
        $transactionId = $payment->tracking_code.'-'.$attempt;
        $amountRial = $this->amountInRial((int) $payment->amount, $config);
        $callbackUrl = url($config['callback_url']).'?payment='.$payment->tracking_code;

        $payload = [
            'amount' => $amountRial,
            'paymentMethodTypeDto' => self::PAYMENT_METHOD_INSTALLMENT,
            'returnURL' => $callbackUrl,
            'transactionId' => $transactionId,
            'cartList' => [$this->buildCartList($order, $config, $amountRial)],
            'discountAmount' => $this->amountInRial((int) ($order->discount_amount ?? 0), $config),
        ];

        $mobile = $this->normalizeMobile((string) ($order->user?->phone ?? ''));
        if ($mobile !== '') {
            $payload['mobile'] = $mobile;
        }

        $payment->update([
            'raw_response' => array_merge($raw, [
                'snapppay_attempt' => $attempt,
                'snapppay_transaction_id' => $transactionId,
            ]),
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_REQUEST,
            'snapppay_token_request',
            'درخواست توکن پرداخت اسنپ‌پی ارسال شد.',
            ['amount_rial' => $amountRial, 'transaction_id' => $transactionId]
        );

        $data = $this->bearerRequest($config, 'api/online/payment/v1/token', 'POST', $payload);

        if (! $this->isSuccessful($data)) {
            $message = $this->errorMessage($data, 'خطا در دریافت توکن اسنپ‌پی');

            $this->failInitiate($payment, $order, $data, $message);

            throw new RuntimeException($message);
        }

        $paymentToken = (string) data_get($data, 'response.paymentToken', '');
        $paymentPageUrl = (string) data_get($data, 'response.paymentPageUrl', '');

        if ($paymentToken === '' || $paymentPageUrl === '') {
            throw new RuntimeException('پاسخ نامعتبر از درگاه اسنپ‌پی.');
        }

        $payment->update([
            'transaction_id' => $paymentToken,
            'raw_response' => array_merge($payment->fresh()->raw_response ?? [], [
                'snapppay_payment_token' => $paymentToken,
                'snapppay_token_response' => $data,
            ]),
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'snapppay_token_created',
            'توکن اسنپ‌پی صادر شد؛ کاربر به درگاه هدایت می‌شود.',
            ['payment_token' => $paymentToken],
            $data
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به درگاه اسنپ‌پی هدایت می‌شود.',
            ['transactionId' => $transactionId]
        );

        return $paymentPageUrl;
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        $config = $this->settings->snapppay();

        if (strtoupper(trim($status)) !== 'OK') {
            return GatewayVerificationResult::canceled(
                ['state' => $status],
                'پرداخت اسنپ‌پی ناموفق یا لغو شد.'
            );
        }

        $paymentToken = $authority !== ''
            ? $authority
            : (string) ($payment->transaction_id ?: data_get($payment->raw_response, 'snapppay_payment_token', ''));

        if ($paymentToken === '') {
            return GatewayVerificationResult::failed(null, 'توکن پرداخت اسنپ‌پی یافت نشد.');
        }

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'snapppay_verify_request',
            'درخواست verify اسنپ‌پی ارسال شد.',
            ['payment_token' => $paymentToken]
        );

        try {
            $verifyData = $this->bearerRequest($config, 'api/online/payment/v1/verify', 'POST', [
                'paymentToken' => $paymentToken,
            ]);
        } catch (\Throwable $e) {
            return GatewayVerificationResult::failed(null, $e->getMessage());
        }

        if (! $this->isSuccessful($verifyData)) {
            return GatewayVerificationResult::failed(
                $verifyData,
                $this->errorMessage($verifyData, 'تأیید پرداخت اسنپ‌پی ناموفق بود.')
            );
        }

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_RESPONSE,
            'snapppay_verified',
            'تراکنش اسنپ‌پی verify شد؛ درخواست settle.',
            null,
            $verifyData
        );

        try {
            $settleData = $this->bearerRequest($config, 'api/online/payment/v1/settle', 'POST', [
                'paymentToken' => $paymentToken,
            ]);
        } catch (\Throwable $e) {
            return GatewayVerificationResult::failed($verifyData, 'verify موفق بود اما settle ناموفق: '.$e->getMessage());
        }

        if (! $this->isSuccessful($settleData)) {
            return GatewayVerificationResult::failed(
                ['verify' => $verifyData, 'settle' => $settleData],
                $this->errorMessage($settleData, 'تسویه (settle) اسنپ‌پی ناموفق بود.')
            );
        }

        $paidAmount = AmountConverter::fromGateway(
            $this->amountInRial((int) $payment->amount, $config),
            (string) ($config['amount_unit'] ?? AmountConverter::UNIT_TOMAN)
        );

        return GatewayVerificationResult::success(
            $paymentToken,
            [
                'snapppay_verify' => $verifyData,
                'snapppay_settle' => $settleData,
                'callback_state' => $status,
            ],
            $paidAmount,
        );
    }

    /** @param  array<string, mixed>  $config */
    protected function buildCartList(Order $order, array $config, int $totalAmountRial): array
    {
        $shippingRial = $this->amountInRial((int) ($order->shipping_amount ?? 0), $config);
        $items = [];

        foreach ($order->items as $item) {
            if (! $item instanceof OrderItem) {
                continue;
            }

            $items[] = [
                'name' => $this->itemName($item),
                'count' => max(1, (int) $item->quantity),
                'amount' => $this->amountInRial((int) $item->price, $config),
                'id' => (int) ($item->product_id ?: $item->id),
                'category' => $this->itemCategoryName($item),
            ];
        }

        if ($items === []) {
            $items[] = [
                'name' => 'سفارش '.$order->tracking_code,
                'count' => 1,
                'amount' => $totalAmountRial,
                'id' => (int) $order->id,
                'category' => '',
            ];
        }

        return [
            'cartId' => (int) $order->id,
            'totalAmount' => $totalAmountRial,
            'cartItems' => $items,
            'taxAmount' => 0,
            'shippingAmount' => $shippingRial,
            'isShipmentIncluded' => $shippingRial > 0,
            'isTaxIncluded' => false,
        ];
    }

    protected function itemName(OrderItem $item): string
    {
        $name = trim((string) ($item->product_name ?? ''));
        if ($name === '' && $item->relationLoaded('product') && $item->product) {
            $name = trim((string) $item->product->name);
        }

        return $name !== '' ? mb_substr($name, 0, 200) : 'محصول';
    }

    protected function itemCategoryName(OrderItem $item): string
    {
        $product = $item->product;

        return mb_substr(trim((string) ($product?->category?->name ?? '')), 0, 100);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function bearerRequest(array $config, string $endpoint, string $method, array $body = []): array
    {
        $url = $this->apiBase($config).'/'.ltrim($endpoint, '/');

        $response = $this->httpClient($config)
            ->send($method, $url, ['json' => $body]);

        if ($response->status() === 401) {
            $this->clearAccessToken($config);
            $response = $this->httpClient($config)
                ->send($method, $url, ['json' => $body]);
        }

        return $this->decodeResponse($response->body(), $response->status());
    }

    /** @return array<string, mixed> */
    protected function requestOAuthToken(array $config): array
    {
        $url = $this->apiBase($config).'/api/online/v1/oauth/token';

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.$this->basicCredentials($config),
                'user-agent' => 'SnappPay, '.(string) ($config['client_id'] ?? ''),
            ])
            ->post($url, [
                'grant_type' => 'password',
                'scope' => 'online-merchant',
                'username' => (string) ($config['client_username'] ?? ''),
                'password' => (string) ($config['client_password'] ?? ''),
            ]);

        if ($response->status() === 401) {
            throw new RuntimeException('احراز هویت اسنپ‌پی ناموفق بود. client_id، client_secret، نام کاربری یا رمز را بررسی کنید.');
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['access_token'])) {
            throw new RuntimeException('توکن دسترسی اسنپ‌پی دریافت نشد.');
        }

        return $data;
    }

    /** @param  array<string, mixed>  $config */
    protected function httpClient(array $config): PendingRequest
    {
        $token = $this->accessToken($config);

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'user-agent' => 'SnappPay, '.(string) ($config['client_id'] ?? ''),
        ])->timeout(45);
    }

    /** @param  array<string, mixed>  $config */
    protected function accessToken(array $config): string
    {
        $cacheKey = $this->tokenCacheKey($config);
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $data = $this->requestOAuthToken($config);
        $token = (string) $data['access_token'];
        $ttl = max(60, (int) ($data['expires_in'] ?? 3600) - 30);

        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /** @param  array<string, mixed>  $config */
    protected function clearAccessToken(array $config): void
    {
        Cache::forget($this->tokenCacheKey($config));
    }

    /** @param  array<string, mixed>  $config */
    protected function tokenCacheKey(array $config): string
    {
        return 'snapppay:bearer:'.md5((string) ($config['client_id'] ?? '').'|'.($config['base_url'] ?? ''));
    }

    /** @param  array<string, mixed>  $config */
    protected function basicCredentials(array $config): string
    {
        $id = (string) ($config['client_id'] ?? '');
        $secret = (string) ($config['client_secret'] ?? '');

        return base64_encode($id.':'.$secret);
    }

    /** @param  array<string, mixed>  $config */
    protected function apiBase(array $config): string
    {
        return rtrim((string) ($config['base_url'] ?? 'https://api.snapppay.ir'), '/');
    }

    /** @param  array<string, mixed>  $config */
    protected function amountInRial(int $shopAmount, array $config): int
    {
        return AmountConverter::toGateway($shopAmount, (string) ($config['amount_unit'] ?? AmountConverter::UNIT_TOMAN));
    }

    /** @return array<string, mixed> */
    protected function decodeResponse(string $body, int $status): array
    {
        $data = json_decode($body, true);

        if (! is_array($data)) {
            throw new RuntimeException('پاسخ نامعتبر از اسنپ‌پی (HTTP '.$status.').');
        }

        return $data;
    }

    /** @param  array<string, mixed>|null  $data */
    protected function isSuccessful(?array $data): bool
    {
        return (bool) ($data['successful'] ?? false);
    }

    /** @param  array<string, mixed>|null  $data */
    protected function errorMessage(?array $data, string $fallback): string
    {
        $message = trim((string) data_get($data, 'errorData.message', ''));

        return $message !== '' ? $message : $fallback;
    }

    /** @param  array<string, mixed>  $config */
    protected function assertCredentials(array $config): void
    {
        foreach (['client_id', 'client_secret', 'client_username', 'client_password', 'base_url'] as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new RuntimeException('تنظیمات درگاه اسنپ‌پی ناقص است.');
            }
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function failInitiate(Payment $payment, Order $order, array $data, string $message): void
    {
        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'raw_response' => array_merge(is_array($payment->raw_response) ? $payment->raw_response : [], [
                'snapppay_token_error' => $data,
            ]),
        ]);

        $this->audit->failure(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'snapppay_token_failed',
            $message,
            null,
            [],
            $data
        );

        $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, $message);
        $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);
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

        return strlen($digits) === 11 && str_starts_with($digits, '09') ? $digits : '';
    }
}