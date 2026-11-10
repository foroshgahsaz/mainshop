<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Rules\DarikMemberCode;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use RuntimeException;

class DarikGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected DarikApiClient $api,
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->darik();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه داریک فعال نیست.');
        }

        $this->assertCredentials($config);

        $order->loadMissing(['user', 'items.product', 'address']);

        $memberCode = $this->resolveMemberCode($payment, $order);
        $amounts = $this->amountPayload($payment, $config);
        $merchant = $this->merchantPayload($config, $order);

        $receiptNumber = 'RCP-'.$payment->tracking_code;
        $orderId = 'ORD-'.$payment->tracking_code;

        $orderBody = array_merge($merchant, $amounts, [
            'MemberCode' => $memberCode,
            'ReceiptNumber' => $receiptNumber,
            'OrderId' => $orderId,
            'OrderDate' => now()->format('Y-m-d H:i:s'),
            'DetailedInfo' => $this->detailedInfo($order),
        ]);

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'darik_order_request',
            'درخواست ثبت سفارش به داریک ارسال شد.',
            [
                'order_id' => $orderId,
                'receipt_number' => $receiptNumber,
            ]
        );

        [$orderResponse, $orderData] = $this->api->post($config, '/api/v1/Order', $orderBody);

        try {
            $this->api->assertSuccess($orderResponse, $orderData, 'ثبت سفارش داریک ناموفق بود.');
        } catch (RuntimeException $e) {
            return $this->failInitiate($payment, $order, $orderData, $e->getMessage());
        }

        $transactionToken = (string) data_get($orderData, 'TransactionToken', '');
        $referenceCode = (int) data_get($orderData, 'ReferenceCode', 0);

        if ($transactionToken === '' || $referenceCode <= 0) {
            return $this->failInitiate($payment, $order, $orderData, 'پاسخ ثبت سفارش داریک ناقص بود.');
        }

        $otpBody = array_merge($amounts, [
            'ReferenceCode' => $referenceCode,
            'TransactionToken' => $transactionToken,
            'ReceiptNumber' => $receiptNumber,
            'OrderId' => $orderId,
        ]);

        [$otpResponse, $otpData] = $this->api->post($config, '/api/v1/SendOTP', $otpBody);

        try {
            $this->api->assertSuccess($otpResponse, $otpData, 'ارسال رمز یکبارمصرف داریک ناموفق بود.');
        } catch (RuntimeException $e) {
            return $this->failInitiate($payment, $order, array_merge($orderData, ['send_otp' => $otpData]), $e->getMessage());
        }

        $darikState = [
            'member_code' => $memberCode,
            'receipt_number' => $receiptNumber,
            'order_id' => $orderId,
            'reference_code' => $referenceCode,
            'transaction_token' => $transactionToken,
            'total_amount' => $amounts['TotalAmount'],
            'pay_credit_amount' => $amounts['PayCreditAmount'],
            'otp_sent_at' => now()->toIso8601String(),
        ];

        $payment->update([
            'transaction_id' => (string) $referenceCode,
            'raw_response' => [
                'darik' => $darikState,
                'order' => $orderData,
                'send_otp' => $otpData,
            ],
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'darik_otp_sent',
            'رمز خرید داریک برای مشتری ارسال شد.',
            ['reference_code' => $referenceCode]
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'رمز خرید داریک به موبایل مشتری ارسال شد.',
            ['referenceCode' => $referenceCode]
        );

        return route('payment.darik.otp', ['tracking' => $payment->tracking_code]);
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        if ($status === 'cancel') {
            return GatewayVerificationResult::canceled(null, 'انصراف از پرداخت داریک');
        }

        $otp = preg_replace('/\D+/', '', $authority) ?? '';
        if ($otp === '' || strlen($otp) !== 5) {
            return GatewayVerificationResult::failed(null, 'رمز خرید داریک باید ۵ رقم باشد.');
        }

        $config = $this->settings->darik();
        $darik = data_get($payment->raw_response, 'darik', []);

        if (! is_array($darik) || $darik === []) {
            return GatewayVerificationResult::failed(null, 'اطلاعات تراکنش داریک یافت نشد.');
        }

        $body = [
            'ReferenceCode' => (int) ($darik['reference_code'] ?? 0),
            'TransactionToken' => (string) ($darik['transaction_token'] ?? ''),
            'ReceiptNumber' => (string) ($darik['receipt_number'] ?? ''),
            'OrderId' => (string) ($darik['order_id'] ?? ''),
            'TotalAmount' => (int) ($darik['total_amount'] ?? 0),
            'PayCreditAmount' => (int) ($darik['pay_credit_amount'] ?? 0),
            'OTP' => (int) $otp,
        ];

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'darik_confirm_request',
            'درخواست تأیید نهایی داریک ارسال شد.',
            ['reference_code' => $body['ReferenceCode']]
        );

        [$response, $data] = $this->api->post($config, '/api/v1/Confirm', $body);

        try {
            $this->api->assertSuccess($response, $data, 'تأیید پرداخت داریک ناموفق بود.');
        } catch (RuntimeException $e) {
            $errorCode = (string) data_get($data, 'errorCode', '');
            if (strtoupper($errorCode) === 'ORDER_ALREADY_COMPLETED') {
                return $this->verificationFromInquiry($config, $darik, $data);
            }

            return GatewayVerificationResult::failed($data, $e->getMessage());
        }

        $paidAmount = AmountConverter::fromGateway(
            (int) data_get($data, 'PayCreditAmount', $darik['pay_credit_amount'] ?? 0),
            $config['amount_unit']
        );

        return GatewayVerificationResult::success(
            (string) ($darik['reference_code'] ?? ''),
            array_merge(is_array($payment->raw_response) ? $payment->raw_response : [], ['confirm' => $data]),
            $paidAmount
        );
    }

    public function resendOtp(Payment $payment): void
    {
        if ($payment->gateway !== 'darik' || $payment->status !== Payment::STATUS_PENDING) {
            throw new RuntimeException('این پرداخت برای ارسال مجدد رمز داریک معتبر نیست.');
        }

        $config = $this->settings->darik();
        $darik = data_get($payment->raw_response, 'darik', []);

        if (! is_array($darik) || empty($darik['transaction_token'])) {
            throw new RuntimeException('اطلاعات تراکنش داریک یافت نشد.');
        }

        $body = [
            'ReferenceCode' => (int) ($darik['reference_code'] ?? 0),
            'TransactionToken' => (string) ($darik['transaction_token'] ?? ''),
            'ReceiptNumber' => (string) ($darik['receipt_number'] ?? ''),
            'OrderId' => (string) ($darik['order_id'] ?? ''),
            'TotalAmount' => (int) ($darik['total_amount'] ?? 0),
            'PayCreditAmount' => (int) ($darik['pay_credit_amount'] ?? 0),
        ];

        [$response, $data] = $this->api->post($config, '/api/v1/SendOTP', $body);
        $this->api->assertSuccess($response, $data, 'ارسال مجدد رمز داریک ناموفق بود.');

        $payment->update([
            'raw_response' => array_merge(
                is_array($payment->raw_response) ? $payment->raw_response : [],
                [
                    'darik' => array_merge($darik, ['otp_resent_at' => now()->toIso8601String()]),
                    'send_otp' => $data,
                ]
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $darik
     * @param  array<string, mixed>  $confirmData
     */
    protected function verificationFromInquiry(array $config, array $darik, array $confirmData): GatewayVerificationResult
    {
        $body = [
            'ReferenceCode' => (int) ($darik['reference_code'] ?? 0),
            'TransactionToken' => (string) ($darik['transaction_token'] ?? ''),
            'ReceiptNumber' => (string) ($darik['receipt_number'] ?? ''),
            'OrderId' => (string) ($darik['order_id'] ?? ''),
        ];

        try {
            [$response, $data] = $this->api->post($config, '/api/v1/Inquiry', $body);
            $this->api->assertSuccess($response, $data, 'استعلام تراکنش داریک ناموفق بود.');
        } catch (RuntimeException $e) {
            return GatewayVerificationResult::failed($confirmData, $e->getMessage());
        }

        $status = strtolower((string) data_get($data, 'Status', ''));
        if ($status !== 'success') {
            return GatewayVerificationResult::failed($data, 'تراکنش داریک هنوز تکمیل نشده است.');
        }

        $paidAmount = AmountConverter::fromGateway(
            (int) data_get($data, 'PayCreditAmount', $darik['pay_credit_amount'] ?? 0),
            $config['amount_unit']
        );

        return GatewayVerificationResult::success(
            (string) ($darik['reference_code'] ?? ''),
            $data,
            $paidAmount
        );
    }

    /** @param  array<string, mixed>  $config */
    protected function amountPayload(Payment $payment, array $config): array
    {
        $total = AmountConverter::toGateway($payment->amount, $config['amount_unit']);
        $credit = $total;

        return [
            'TotalAmount' => $total,
            'PayCreditAmount' => $credit,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function merchantPayload(array $config, Order $order): array
    {
        $provinceId = (int) ($config['province_id'] ?? 8);
        $city = $order->address?->city ?? '';

        return [
            'MerchantCode' => (int) $config['merchant_code'],
            'MerchantGroupId' => (int) $config['merchant_group_id'],
            'ProvinceId' => $provinceId,
            'City' => mb_substr((string) $city, 0, 64),
        ];
    }

    protected function resolveMemberCode(Payment $payment, Order $order): string
    {
        $fromPayment = DarikMemberCode::normalize(data_get($payment->raw_response, 'darik.member_code', ''));
        if ($fromPayment !== '' && preg_match('/^5892\d{12}$/', $fromPayment)) {
            return $fromPayment;
        }

        $fromUser = DarikMemberCode::normalize($order->user?->darik_member_code ?? '');
        if ($fromUser !== '' && preg_match('/^5892\d{12}$/', $fromUser)) {
            return $fromUser;
        }

        throw new RuntimeException('شناسه ۱۶ رقمی کیف پول داریک (شروع با 5892) برای مشتری ثبت نشده است.');
    }

    protected function detailedInfo(Order $order): string
    {
        $parts = [];
        foreach ($order->items->take(5) as $item) {
            $title = $item->product?->title ?? $item->title ?? 'محصول';
            $parts[] = mb_substr($title, 0, 80);
        }

        $text = $parts !== [] ? implode('؛ ', $parts) : 'خرید از فروشگاه';

        return mb_substr($text, 0, 2000);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    protected function failInitiate(Payment $payment, Order $order, array $raw, string $message): string
    {
        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'raw_response' => $raw,
        ]);

        $this->audit->failure(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'darik_initiate_failed',
            $message,
            null,
            [],
            $raw
        );

        $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, $message);
        $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);

        throw new RuntimeException($message);
    }

    /** @param  array<string, mixed>  $config */
    protected function assertCredentials(array $config): void
    {
        foreach (['api_key', 'username', 'password', 'merchant_code', 'merchant_group_id', 'base_url'] as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new RuntimeException('تنظیمات درگاه داریک ناقص است.');
            }
        }
    }
}
