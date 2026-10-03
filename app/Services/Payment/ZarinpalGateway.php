<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZarinpalGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
        protected SettingsService $settings,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = $this->settings->zarinpal();

        if (! ($config['enabled'] ?? true)) {
            throw new RuntimeException('درگاه پرداخت فعال نیست.');
        }

        if (empty($config['merchant_id'])) {
            throw new RuntimeException('تنظیمات درگاه پرداخت ناقص است.');
        }

        $baseUrl = $config['sandbox']
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://payment.zarinpal.com/pg/v4/payment';

        $callbackUrl = url($config['callback_url']).'?payment='.$payment->tracking_code;

        $description = $this->paymentDescription($payment, $order);

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'zarinpal_request',
            'درخواست authority به زرین‌پال ارسال شد.',
            ['amount' => $payment->amount]
        );

        $response = Http::post("{$baseUrl}/request.json", [
            'merchant_id' => $config['merchant_id'],
            'amount' => AmountConverter::toGateway($payment->amount, $config['amount_unit'] ?? AmountConverter::UNIT_TOMAN),
            'callback_url' => $callbackUrl,
            'description' => $description,
            'metadata' => [
                'mobile' => $order->user->phone,
                'email' => $order->user->email,
            ],
        ]);

        $data = $response->json('data');

        if ($response->failed() || empty($data['authority'])) {
            $previous = $payment->status;
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'raw_response' => $response->json(),
            ]);

            $this->audit->failure(
                $payment->fresh(),
                PaymentAuditStep::GATEWAY_RESPONSE,
                'zarinpal_request_failed',
                'پاسخ نامعتبر از زرین‌پال (request).',
                null,
                ['http_status' => $response->status()]
            );

            $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, 'خطا در اتصال به درگاه');
            $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);

            throw new RuntimeException('خطا در اتصال به درگاه پرداخت.');
        }

        $payment->update([
            'transaction_id' => $data['authority'],
            'raw_response' => $response->json(),
        ]);

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::GATEWAY_RESPONSE,
            'zarinpal_authority_received',
            'Authority از زرین‌پال دریافت شد.',
            ['authority' => $data['authority']]
        );

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به درگاه زرین‌پال هدایت شد.',
            ['authority' => $data['authority']]
        );

        $startPayUrl = $config['sandbox']
            ? 'https://sandbox.zarinpal.com/pg/StartPay/'
            : 'https://www.zarinpal.com/pg/StartPay/';

        return $startPayUrl.$data['authority'];
    }

    public function verify(Payment $payment, string $authority, string $status): GatewayVerificationResult
    {
        if ($status !== 'OK' || $authority !== $payment->transaction_id) {
            return GatewayVerificationResult::canceled(
                ['status' => $status, 'authority' => $authority],
                'انصراف یا لغو توسط کاربر'
            );
        }

        $config = $this->settings->zarinpal();
        $baseUrl = $config['sandbox']
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://payment.zarinpal.com/pg/v4/payment';

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'zarinpal_verify_request',
            'درخواست verify به زرین‌پال ارسال شد.',
            ['authority' => $authority]
        );

        $response = Http::post("{$baseUrl}/verify.json", [
            'merchant_id' => $config['merchant_id'],
            'amount' => AmountConverter::toGateway($payment->amount, $config['amount_unit'] ?? AmountConverter::UNIT_TOMAN),
            'authority' => $authority,
        ]);

        $code = $response->json('data.code');

        if ($response->successful() && in_array($code, [100, 101], true)) {
            return GatewayVerificationResult::success(
                $response->json('data.card_pan'),
                $response->json()
            );
        }

        return GatewayVerificationResult::failed($response->json(), 'تأیید درگاه ناموفق');
    }

    protected function paymentDescription(Payment $payment, Order $order): string
    {
        if ($payment->wasPaidByRepresentative()) {
            $payment->loadMissing('paidByRepresentative');
            $order->loadMissing('user');

            $repName = $payment->paidByRepresentative?->name ?? 'نماینده';
            $customerName = $order->user?->name ?? 'مشتری';

            return "پیش‌فاکتور {$order->tracking_code} — پرداخت نماینده: {$repName} — مشتری: {$customerName}";
        }

        return "پرداخت سفارش {$order->tracking_code}";
    }
}
