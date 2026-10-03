<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BajetRefundService
{
    public function __construct(
        protected SettingsService $settings,
        protected BajetPayGateway $gateway,
        protected PaymentActivityLogger $paymentLog,
        protected OrderActivityLogger $orderLog,
    ) {}

    public function refund(Payment $payment, ?int $shopAmount = null): Payment
    {
        if ($payment->gateway !== 'bajet') {
            throw new RuntimeException('این پرداخت مربوط به باجت‌پی نیست.');
        }

        if ($payment->status !== Payment::STATUS_SUCCESS) {
            throw new RuntimeException('فقط پرداخت موفق باجت‌پی قابل استرداد است.');
        }

        $referenceId = (string) $payment->transaction_id;
        if ($referenceId === '') {
            throw new RuntimeException('شناسه تراکنش باجت‌پی یافت نشد.');
        }

        $config = $this->settings->bajet();
        $amount = $shopAmount ?? $payment->amount;
        if ($amount <= 0 || $amount > $payment->amount) {
            throw new RuntimeException('مبلغ استرداد نامعتبر است.');
        }

        $trackId = strtoupper(Str::random(12));
        $payload = [
            'referenceId' => $referenceId,
            'trackId' => $trackId,
        ];

        if ($amount < $payment->amount) {
            $payload['amount'] = AmountConverter::toGateway($amount, $config['amount_unit']);
        }

        $response = Http::baseUrl(rtrim((string) $config['base_url'], '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->withToken($this->gateway->accessToken($config), 'Bearer')
            ->post('/api/v1/jetpay/refund', $payload);

        $data = $response->json();
        $data = is_array($data) ? $data : [];
        $success = (bool) data_get($data, 'success', false);

        if ($response->failed() || ! $success) {
            $message = data_get($data, 'result.error.fa', data_get($data, 'result.error.en', 'استرداد باجت‌پی ناموفق بود.'));

            throw new RuntimeException(is_string($message) ? $message : 'استرداد باجت‌پی ناموفق بود.');
        }

        $inquiry = Http::baseUrl(rtrim((string) $config['base_url'], '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->withToken($this->gateway->accessToken($config), 'Bearer')
            ->post('/api/v1/jetpay/refund-inquiry', [
                'referenceId' => $referenceId,
                'trackId' => $trackId,
            ]);

        $inquiryData = $inquiry->json();
        $inquiryData = is_array($inquiryData) ? $inquiryData : [];
        $refundStatus = (string) data_get($inquiryData, 'result.status', '');

        if (! in_array($refundStatus, ['SUCCEEDED', 'CREDIT_PENDING', 'CASH_PENDING'], true)) {
            throw new RuntimeException('استرداد ثبت شد اما وضعیت نهایی هنوز موفق نیست: '.$refundStatus);
        }

        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'raw_response' => array_merge(is_array($payment->raw_response) ? $payment->raw_response : [], [
                'refund' => $data,
                'refund_inquiry' => $inquiryData,
            ]),
        ]);

        $payment = $payment->fresh();
        $this->paymentLog->statusChanged($payment, $previous, Payment::STATUS_REFUNDED, 'استرداد باجت‌پی (trackId: '.$trackId.')');

        $order = $payment->order;
        if ($order instanceof Order && ! $order->payments()->where('status', Payment::STATUS_SUCCESS)->exists()) {
            $orderPrevious = $order->status;
            if (in_array($order->status, [Order::STATUS_PROCESSING, Order::STATUS_PROFORMA], true)) {
                $order->update(['status' => Order::STATUS_PENDING]);
                $this->orderLog->statusChanged($order->fresh(), $orderPrevious, Order::STATUS_PENDING, 'پس از استرداد باجت‌پی');
            }
        }

        return $payment;
    }
}
