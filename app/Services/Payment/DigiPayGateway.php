<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DigiPayGateway implements PaymentGatewayInterface
{
    private const API_VERSION = '2022-02-02';

    private const PLUGIN_VERSION = '1.0.0';

    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->digipay();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه دیجی‌پی فعال نیست.');
        }

        $this->assertCredentials($config);

        $order->loadMissing(['user', 'items.product.category', 'items.product.brand']);

        $mobile = $this->normalizeMobile((string) ($order->user?->phone ?? ''));
        if ($mobile === '') {
            throw new RuntimeException('شماره موبایل مشتری برای پرداخت دیجی‌پی الزامی است.');
        }

        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];
        $attempt = (int) ($raw['digipay_attempt'] ?? 0) + 1;
        $providerId = $payment->tracking_code.'___'.$attempt;
        $amountRial = $this->amountInRial((int) $payment->amount, $config);
        $callbackUrl = url($config['callback_url']).'?payment='.$payment->tracking_code;
        $ticketType = (int) ($config['ticket_type'] ?? 11);

        $payment->update([
            'raw_response' => array_merge($raw, [
                'digipay_attempt' => $attempt,
                'digipay_provider_id' => $providerId,
                'digipay_ticket_type' => $ticketType,
            ]),
        ]);

        $payload = [
            'amount' => $amountRial,
            'cellNumber' => $mobile,
            'providerId' => $providerId,
            'callbackUrl' => $callbackUrl,
            'basketDetailsDto' => [
                'basketId' => $providerId,
                'items' => $this->basketItems($order, $config),
            ],
        ];

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_REQUEST,
            'digipay_create_ticket',
            'درخواست ایجاد تیکت دیجی‌پی ارسال شد.',
            ['amount_rial' => $amountRial, 'provider_id' => $providerId]
        );

        $data = $this->apiRequest($config, 'tickets/business?type='.$ticketType, $payload, true);

        $status = (int) data_get($data, 'result.status', -1);
        if ($status !== 0) {
            $message = (string) data_get($data, 'result.message', 'خطا در ایجاد تیکت دیجی‌پی');
            $this->failInitiate($payment, $order, $data, $message);

            throw new RuntimeException($message);
        }

        $redirectUrl = (string) data_get($data, 'redirectUrl', '');
        if ($redirectUrl === '') {
            throw new RuntimeException('آدرس هدایت دیجی‌پی دریافت نشد.');
        }

        $payment->update([
            'raw_response' => array_merge($payment->fresh()->raw_response ?? [], [
                'digipay_ticket' => data_get($data, 'ticket'),
            ]),
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'digipay_ticket_created',
            'تیکت دیجی‌پی ایجاد شد؛ کاربر به درگاه هدایت می‌شود.',
            ['redirect_host' => parse_url($redirectUrl, PHP_URL_HOST)]
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به درگاه دیجی‌پی هدایت می‌شود.',
            ['providerId' => $providerId]
        );

        return $redirectUrl;
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        $config = $this->settings->digipay();
        $trackingCode = $authority !== '' ? $authority : (string) $payment->transaction_id;

        if (strtoupper(trim($status)) !== 'SUCCESS') {
            return GatewayVerificationResult::canceled(
                ['result' => $status, 'trackingCode' => $trackingCode],
                'پرداخت دیجی‌پی ناموفق یا لغو شد.'
            );
        }

        if ($trackingCode === '') {
            return GatewayVerificationResult::failed(null, 'کد رهگیری دیجی‌پی دریافت نشد.');
        }

        $callback = request()->only(['amount', 'type', 'trackingCode', 'result']);
        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];
        $providerId = (string) ($raw['digipay_provider_id'] ?? $payment->tracking_code);
        $type = (string) ($callback['type'] ?? $raw['digipay_type'] ?? '0');
        $callbackAmount = (int) ($callback['amount'] ?? 0);
        $expectedRial = $this->amountInRial((int) $payment->amount, $config);

        if ($callbackAmount > 0 && $callbackAmount !== $expectedRial) {
            return GatewayVerificationResult::failed(
                $callback,
                'مبلغ پرداختی دیجی‌پی با سفارش مطابقت ندارد.'
            );
        }

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'digipay_verify_request',
            'درخواست تأیید تراکنش دیجی‌پی ارسال شد.',
            ['trackingCode' => $trackingCode, 'providerId' => $providerId, 'type' => $type]
        );

        try {
            $verified = $this->verifyTicket($config, $trackingCode, $providerId, $type);
        } catch (\Throwable $e) {
            return GatewayVerificationResult::failed($callback, $e->getMessage());
        }

        if (! $verified) {
            return GatewayVerificationResult::failed($callback, 'تأیید تراکنش دیجی‌پی ناموفق بود.');
        }

        if (in_array($type, ['5', '13'], true)) {
            $this->tryDeliver($payment, $config, $trackingCode, $type);
        }

        $paidAmount = AmountConverter::fromGateway(
            $expectedRial,
            (string) ($config['amount_unit'] ?? AmountConverter::UNIT_TOMAN)
        );

        return GatewayVerificationResult::success(
            $trackingCode,
            array_merge($callback, [
                'digipay_provider_id' => $providerId,
                'digipay_type' => $type,
            ]),
            $paidAmount,
        );
    }

    /** @param  array<string, mixed>  $config */
    protected function verifyTicket(array $config, string $trackingCode, string $providerId, string $type, int $retries = 0): bool
    {
        if ($retries > 3) {
            throw new RuntimeException('حداکثر تلاش برای تأیید دیجی‌پی انجام شد.');
        }

        $data = $this->apiRequest($config, 'purchases/verify?type='.$type, [
            'trackingCode' => $trackingCode,
            'providerId' => $providerId,
        ], true);

        $status = (int) data_get($data, 'result.status', -1);

        if ($status === 0) {
            return true;
        }

        if ($status === 9011) {
            sleep(5);

            return $this->verifyTicket($config, $trackingCode, $providerId, $type, $retries + 1);
        }

        $message = (string) data_get($data, 'result.message', 'خطای تأیید دیجی‌پی');

        throw new RuntimeException($message);
    }

    /** @param  array<string, mixed>  $config */
    protected function tryDeliver(Payment $payment, array $config, string $trackingCode, string $type): void
    {
        $order = $payment->order;
        if (! $order) {
            return;
        }

        $order->loadMissing('items');
        $products = $order->items->map(fn ($item) => (string) $item->name)->values()->all();

        try {
            $this->apiRequest($config, 'purchases/deliver?type='.$type, [
                'deliveryDate' => (int) round(microtime(true) * 1000),
                'invoiceNumber' => (string) $order->id,
                'trackingCode' => $trackingCode,
                'products' => $products,
            ], true);
        } catch (\Throwable) {
            // Deliver failure should not void a verified payment.
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function apiRequest(array $config, string $endpoint, array $payload, bool $json, bool $retryAuth = true): array
    {
        $token = $this->accessToken($config);
        $headers = $json ? ['Authorization' => 'Bearer '.$token] : [];

        $response = $this->send($config, $endpoint, $payload, $json, $headers);

        if ($response->status() === 401 && $retryAuth && $endpoint !== 'oauth/token') {
            $this->clearTokens();
            $token = $this->accessToken($config, forcePassword: true);
            $headers = $json ? ['Authorization' => 'Bearer '.$token] : [];

            $response = $this->send($config, $endpoint, $payload, $json, $headers);
        }

        return $this->parseResponse($response, $endpoint);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $extraHeaders
     */
    protected function send(array $config, string $endpoint, array $payload, bool $json, array $extraHeaders = []): Response
    {
        $request = $this->http($config)->withHeaders(array_merge([
            'Agent' => 'WEB',
            'Digipay-Version' => self::API_VERSION,
            'Plugin-Version' => self::PLUGIN_VERSION,
        ], $extraHeaders));

        if ($json) {
            $request = $request->asJson();
        } else {
            $request = $request->asForm();
        }

        return $request->post($this->endpointPath($endpoint), $payload);
    }

    /** @param  array<string, mixed>  $config */
    protected function accessToken(array $config, bool $forcePassword = false): string
    {
        $access = (string) ($config['access_token'] ?? '');
        $refresh = (string) ($config['refresh_token'] ?? '');

        if (! $forcePassword && $access !== '') {
            return $access;
        }

        $body = (! $forcePassword && $refresh !== '')
            ? ['refresh_token' => $refresh, 'grant_type' => 'refresh_token']
            : [
                'username' => $config['username'],
                'password' => $config['password'],
                'grant_type' => 'password',
            ];

        $response = $this->send($config, 'oauth/token', $body, false, [
            'Authorization' => 'Basic '.base64_encode($config['client_id'].':'.$config['client_secret']),
        ]);

        $data = $this->parseResponse($response, 'oauth/token');
        $access = (string) ($data['access_token'] ?? '');
        $refresh = (string) ($data['refresh_token'] ?? '');

        if ($access === '') {
            throw new RuntimeException('دریافت توکن دیجی‌پی ناموفق بود.');
        }

        $this->settings->setMany('digipay', [
            'access_token' => $access,
            'refresh_token' => $refresh,
        ]);

        return $access;
    }

    protected function clearTokens(): void
    {
        $this->settings->setMany('digipay', [
            'access_token' => '',
            'refresh_token' => '',
        ]);
    }

    /** @return array<string, mixed> */
    protected function parseResponse(Response $response, string $endpoint): array
    {
        $data = $response->json();

        if (! is_array($data)) {
            $data = ['body' => $response->body(), 'status' => $response->status()];
        }

        if ($response->failed() && $endpoint === 'oauth/token') {
            $message = (string) data_get($data, 'result.message', data_get($data, 'error', 'خطا در احراز هویت دیجی‌پی'));

            throw new RuntimeException($message);
        }

        if ($response->failed()) {
            $message = (string) data_get($data, 'result.message', $response->body());

            throw new RuntimeException($message !== '' ? $message : 'خطا در ارتباط با دیجی‌پی');
        }

        return $data;
    }

    protected function endpointPath(string $endpoint): string
    {
        return ltrim($endpoint, '/');
    }

    /** @param  array<string, mixed>  $config */
    protected function http(array $config): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $config['base_url'], '/').'/')
            ->acceptJson()
            ->timeout(45);
    }

    /** @param  array<string, mixed>  $config */
    protected function amountInRial(int $shopAmount, array $config): int
    {
        $unit = (string) ($config['amount_unit'] ?? AmountConverter::UNIT_TOMAN);

        return AmountConverter::toGateway($shopAmount, $unit === AmountConverter::UNIT_RIAL ? AmountConverter::UNIT_RIAL : AmountConverter::UNIT_TOMAN);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array<string, mixed>>
     */
    protected function basketItems(Order $order, array $config): array
    {
        $productType = (int) ($config['default_product_type'] ?? 1);
        $items = [];

        foreach ($order->items as $item) {
            $product = $item->product;
            $items[] = [
                'sellerId' => 1,
                'supplierId' => 1,
                'productCode' => (string) ($product?->sku ?: $product?->id ?: $item->id),
                'brand' => mb_substr((string) ($product?->brand?->name ?? ''), 0, 64),
                'productType' => $productType,
                'count' => max(1, (int) $item->quantity),
                'categoryId' => (string) ($product?->category_id ?? '0'),
            ];
        }

        if ($items === []) {
            $items[] = [
                'sellerId' => 1,
                'supplierId' => 1,
                'productCode' => (string) $order->tracking_code,
                'brand' => '',
                'productType' => $productType,
                'count' => 1,
                'categoryId' => '0',
            ];
        }

        return $items;
    }

    /** @param  array<string, mixed>  $config */
    protected function assertCredentials(array $config): void
    {
        foreach (['username', 'password', 'client_id', 'client_secret', 'base_url'] as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new RuntimeException('تنظیمات درگاه دیجی‌پی ناقص است.');
            }
        }
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

    /**
     * عودت خرید — مستند UPG بخش ۱۰ (`refunds?type=`).
     *
     * @return array<string, mixed>
     */
    public function submitRefund(string $ticketType, string $refundProviderId, int $amountRial, string $saleTrackingCode): array
    {
        $config = $this->settings->digipay();
        $this->assertCredentials($config);

        return $this->apiRequest($config, 'refunds?type='.rawurlencode($ticketType), [
            'providerId' => $refundProviderId,
            'amount' => $amountRial,
            'saleTrackingCode' => $saleTrackingCode,
        ], true);
    }

    /**
     * پیگیری عودت — مستند UPG بخش ۱۱.
     *
     * @return array<string, mixed>
     */
    public function inquireRefund(string $inquiryId, string $ticketType): array
    {
        $config = $this->settings->digipay();
        $this->assertCredentials($config);

        return $this->apiRequest(
            $config,
            'refunds/'.rawurlencode($inquiryId).'?type='.rawurlencode($ticketType),
            [],
            true
        );
    }

    /** @param  array<string, mixed>  $data */
    protected function failInitiate(Payment $payment, Order $order, array $data, string $message): void
    {
        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'raw_response' => $data,
        ]);

        $this->audit->failure(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'digipay_ticket_failed',
            $message,
            null,
            ['http' => $data]
        );

        $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, $message);
        $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);
    }
}
