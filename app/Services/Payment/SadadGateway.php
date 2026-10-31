<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use RuntimeException;

class SadadGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
        protected SadadIpgClient $client,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->sadad();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه پرداخت فعال نیست.');
        }

        $merchantId = trim((string) ($config['merchant_id'] ?? ''));
        $terminalId = trim((string) ($config['terminal_id'] ?? ''));
        $terminalKey = trim((string) ($config['terminal_key'] ?? ''));

        if ($merchantId === '' || $terminalId === '' || $terminalKey === '') {
            throw new RuntimeException('تنظیمات درگاه سداد ناقص است.');
        }

        $orderId = (string) $payment->id;
        $amountRial = AmountConverter::toGateway(
            $payment->amount,
            $config['amount_unit'] ?? AmountConverter::UNIT_TOMAN
        );

        $returnUrl = SadadReturnUrl::absolute($config, $payment);
        $localDateTime = now()->format('m/d/Y g:i:s a');

        $signData = SadadSign::paymentSignData($terminalId, $orderId, $amountRial, $terminalKey);

        $payload = [
            'TerminalId' => $terminalId,
            'MerchantId' => $merchantId,
            'Amount' => $amountRial,
            'SignData' => $signData,
            'ReturnUrl' => $returnUrl,
            'LocalDateTime' => $localDateTime,
            'OrderId' => $orderId,
        ];

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'sadad_payment_request',
            'درخواست Token به درگاه سداد ارسال شد.',
            [
                'order_id' => $orderId,
                'amount_rial' => $amountRial,
                'return_url' => $returnUrl,
            ]
        );

        try {
            $result = $this->client->paymentRequest($payload);
        } catch (RuntimeException $e) {
            $this->markInitiateFailed($payment, $order, ['exception' => $e->getMessage()]);

            throw $e;
        }

        $resCode = (int) ($result['ResCode'] ?? -1);
        $token = trim((string) ($result['Token'] ?? ''));

        if ($resCode !== 0 || $token === '') {
            $description = (string) ($result['Description'] ?? 'پاسخ نامعتبر از درگاه سداد.');
            $this->markInitiateFailed($payment, $order, $result);

            throw new RuntimeException($description !== '' ? $description : 'خطا در اتصال به درگاه پرداخت.');
        }

        $payment->update([
            'transaction_id' => $token,
            'raw_response' => [
                'request' => [
                    'res_code' => $resCode,
                    'order_id' => $orderId,
                ],
            ],
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'sadad_token_received',
            'Token از درگاه سداد دریافت شد.',
            ['token' => $token],
            $result
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به درگاه سداد هدایت شد.',
            ['token' => $token]
        );

        return $this->client->purchaseRedirectUrl($token);
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        $config = $this->settings->sadad();
        $terminalKey = trim((string) ($config['terminal_key'] ?? ''));

        $callback = data_get($payment->raw_response, 'callback', []);
        $resCode = (string) data_get($callback, 'ResCode', $status);
        $token = (string) data_get($callback, 'token', data_get($callback, 'Token', $authority));

        if ($resCode !== '0') {
            return GatewayVerificationResult::canceled(
                ['res_code' => $resCode, 'token' => $token],
                'انصراف یا لغو توسط کاربر'
            );
        }

        if ($token === '' || ($payment->transaction_id && $token !== $payment->transaction_id)) {
            return GatewayVerificationResult::failed(
                ['token' => $token, 'expected' => $payment->transaction_id],
                'توکن بازگشتی با تراکنش مطابقت ندارد.'
            );
        }

        if ($terminalKey === '') {
            return GatewayVerificationResult::failed([], 'تنظیمات درگاه سداد ناقص است.');
        }

        $signData = SadadSign::verifySignData($token, $terminalKey);

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'sadad_verify_request',
            'درخواست Verify به سداد ارسال شد.',
            ['token' => $token]
        );

        try {
            $result = $this->client->verify($token, $signData);
        } catch (RuntimeException $e) {
            return GatewayVerificationResult::failed(
                ['exception' => $e->getMessage()],
                'خطا در تأیید تراکنش سداد.'
            );
        }

        $verifyCode = (int) ($result['ResCode'] ?? -1);

        if ($verifyCode !== 0) {
            $description = (string) ($result['Description'] ?? 'تأیید درگاه ناموفق');

            return GatewayVerificationResult::failed(
                ['verify' => $result],
                $description !== '' ? $description : 'تأیید درگاه ناموفق'
            );
        }

        $reference = (string) ($result['RetrivalRefNo'] ?? $result['SystemTraceNo'] ?? '');

        return GatewayVerificationResult::success(
            null,
            [
                'verify' => $result,
                'callback' => $callback,
                'reference' => $reference,
            ],
        );
    }

    protected function markInitiateFailed(Payment $payment, Order $order, mixed $raw): void
    {
        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'raw_response' => is_array($raw) ? $raw : ['error' => $raw],
        ]);

        $this->audit->failure(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'sadad_request_failed',
            'پاسخ نامعتبر از درگاه سداد (PaymentRequest).',
            null,
            is_array($raw) ? $raw : ['error' => (string) $raw],
            is_array($raw) ? $raw : ['error' => (string) $raw]
        );

        $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, 'خطا در اتصال به درگاه');
        $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);
    }
}
