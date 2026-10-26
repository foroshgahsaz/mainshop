<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use RuntimeException;
use SoapFault;
use Throwable;

class ParsianGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
        protected PecIpgClient $pec,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->parsian();

        if (! ($config['enabled'] ?? false)) {
            throw new RuntimeException('درگاه پرداخت فعال نیست.');
        }

        $loginAccount = trim((string) ($config['login_account'] ?? ''));
        if ($loginAccount === '') {
            throw new RuntimeException('تنظیمات درگاه پرداخت ناقص است.');
        }

        $callbackUrl = url($config['callback_url']).'?payment='.$payment->tracking_code;
        $amount = AmountConverter::toGateway(
            $payment->amount,
            $config['amount_unit'] ?? AmountConverter::UNIT_TOMAN
        );
        $orderId = (int) $payment->id;
        $originator = (string) ($order->user?->phone ?? '');

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'parsian_sale_request',
            'درخواست Token به درگاه پارسیان ارسال شد.',
            ['amount' => $amount, 'order_id' => $orderId]
        );

        try {
            $result = $this->pec->salePaymentRequest(
                $loginAccount,
                $orderId,
                $amount,
                $callbackUrl,
                $payment->tracking_code,
                $originator,
            );
        } catch (SoapFault $e) {
            $this->markInitiateFailed($payment, $order, ['exception' => $e->getMessage()]);

            throw new RuntimeException('خطا در اتصال به درگاه پرداخت.');
        }

        $saleResult = $result->SalePaymentRequestResult ?? null;
        $status = (string) ($saleResult->Status ?? '');
        $token = (string) ($saleResult->Token ?? '');

        if ($status !== '0' || $token === '') {
            $message = (string) ($saleResult->Message ?? 'پاسخ نامعتبر از درگاه پارسیان.');
            $this->markInitiateFailed($payment, $order, [
                'status' => $status,
                'message' => $message,
            ]);

            throw new RuntimeException($message !== '' ? $message : 'خطا در اتصال به درگاه پرداخت.');
        }

        $payment->update([
            'transaction_id' => $token,
            'raw_response' => [
                'sale' => [
                    'status' => $status,
                    'order_id' => $orderId,
                ],
            ],
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'parsian_token_received',
            'Token از درگاه پارسیان دریافت شد.',
            ['token' => $token]
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به درگاه پارسیان هدایت شد.',
            ['token' => $token]
        );

        return PecIpgClient::PAYMENT_PAGE.'?Token='.urlencode($token);
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        $config = $this->settings->parsian();
        $loginAccount = trim((string) ($config['login_account'] ?? ''));

        $callback = data_get($payment->raw_response, 'callback', []);
        $rrn = (int) data_get($callback, 'RRN', 0);
        $callbackStatus = (string) data_get($callback, 'status', $status);
        $token = (string) data_get($callback, 'Token', $authority);

        if ($callbackStatus !== '0' || $rrn <= 0) {
            return GatewayVerificationResult::canceled(
                ['status' => $callbackStatus, 'token' => $token, 'rrn' => $rrn],
                'انصراف یا لغو توسط کاربر'
            );
        }

        if ($token === '' || ($payment->transaction_id && $token !== $payment->transaction_id)) {
            return GatewayVerificationResult::failed(
                ['token' => $token, 'expected' => $payment->transaction_id],
                'توکن بازگشتی با تراکنش مطابقت ندارد.'
            );
        }

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'parsian_confirm_request',
            'درخواست ConfirmPayment به پارسیان ارسال شد.',
            ['token' => $token]
        );

        try {
            $result = $this->pec->confirmPayment($loginAccount, $token);
        } catch (SoapFault $e) {
            $this->attemptReversal($loginAccount, $token, $payment);

            return GatewayVerificationResult::failed(
                ['exception' => $e->getMessage()],
                'خطا در تأیید تراکنش پارسیان.'
            );
        }

        $confirm = $result->ConfirmPaymentResult ?? null;
        $confirmStatus = (string) ($confirm->Status ?? '');

        if ($confirmStatus !== '0') {
            $this->attemptReversal($loginAccount, $token, $payment);

            return GatewayVerificationResult::failed(
                ['status' => $confirmStatus, 'confirm' => $this->objectToArray($confirm)],
                'تأیید درگاه ناموفق'
            );
        }

        $cardPan = (string) ($confirm->CardNumberMasked ?? '');
        $confirmRrn = (string) ($confirm->RRN ?? $rrn);

        return GatewayVerificationResult::success(
            $cardPan !== '' ? $cardPan : null,
            [
                'rrn' => $confirmRrn,
                'callback' => $callback,
                'confirm' => $this->objectToArray($confirm),
            ],
            null,
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
            'parsian_request_failed',
            'پاسخ نامعتبر از درگاه پارسیان (SalePaymentRequest).',
            null,
            is_array($raw) ? $raw : ['error' => (string) $raw]
        );

        $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, 'خطا در اتصال به درگاه');
        $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);
    }

    protected function attemptReversal(string $loginAccount, string $token, Payment $payment): void
    {
        if ($loginAccount === '' || $token === '') {
            return;
        }

        try {
            $this->pec->reversalRequest($loginAccount, $token);
            $this->audit->step(
                $payment,
                PaymentAuditStep::GATEWAY_REQUEST,
                'parsian_reversal_sent',
                'درخواست برگشت تراکنش (Reversal) به پارسیان ارسال شد.',
                ['token' => $token]
            );
        } catch (Throwable) {
            $this->audit->failure(
                $payment,
                PaymentAuditStep::GATEWAY_RESPONSE,
                'parsian_reversal_failed',
                'برگشت تراکنش پارسیان ناموفق بود.',
                null,
                ['token' => $token]
            );
        }
    }

    /** @return array<string, mixed> */
    protected function objectToArray(?object $object): array
    {
        if ($object === null) {
            return [];
        }

        return json_decode(json_encode($object, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
