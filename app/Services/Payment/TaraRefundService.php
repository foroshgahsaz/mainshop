<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TaraRefundService
{
    public function __construct(
        protected SettingsService $settings,
        protected PaymentActivityLogger $paymentLog,
        protected OrderActivityLogger $orderLog,
    ) {}

    public function refund(Payment $payment, ?int $shopAmount = null): Payment
    {
        if ($payment->gateway !== 'tara') {
            throw new RuntimeException('این پرداخت مربوط به تارا نیست.');
        }

        if ($payment->status !== Payment::STATUS_SUCCESS) {
            throw new RuntimeException('فقط پرداخت موفق تارا قابل مرجوعی است.');
        }

        $config = $this->settings->tara();
        if ($config['refund_principal'] === '' || $config['refund_password'] === '') {
            throw new RuntimeException('تنظیمات مرجوعی تارا ناقص است.');
        }

        $reference = $this->referenceNumber($payment);
        if ($reference === '') {
            throw new RuntimeException('شماره مرجع پرداخت تارا یافت نشد.');
        }

        $amount = $shopAmount ?? $payment->amount;
        if ($amount <= 0 || $amount > $payment->amount) {
            throw new RuntimeException('مبلغ مرجوعی نامعتبر است.');
        }

        $accessCode = $this->refundToken($config);
        $base = rtrim((string) $config['refund_base_url'], '/');
        $gatewayAmount = AmountConverter::toGateway($amount, $config['amount_unit']);
        $fullRefund = $amount === $payment->amount;

        $request = Http::baseUrl($base)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->withToken($accessCode, 'bearer');

        $response = $fullRefund
            ? $request->post('/api/v1/user/purchase/limited/refund/'.$reference, [
                'description' => 'مرجوعی سفارش از پنل فروشگاه',
            ])
            : $request->post('/api/v1/user/purchase/limited/refund/partial/'.$reference, [
                'amount' => $gatewayAmount,
                'items' => [[
                    'name' => 'مرجوعی پرداخت '.$payment->tracking_code,
                    'code' => $payment->tracking_code,
                    'count' => 1,
                    'unit' => 5,
                    'fee' => $gatewayAmount,
                    'group' => (string) $config['default_group'],
                    'groupTitle' => (string) $config['default_group_title'],
                    'data' => '',
                ]],
            ]);

        $data = $response->json();
        $success = (bool) data_get($data, 'success', $response->successful());

        if ($response->failed() || ! $success) {
            throw new RuntimeException((string) (data_get($data, 'data.message', data_get($data, 'message')) ?: 'مرجوعی تارا ناموفق بود.'));
        }

        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'raw_response' => array_merge(is_array($payment->raw_response) ? $payment->raw_response : [], [
                'refund' => $data,
            ]),
        ]);

        $this->paymentLog->statusChanged(
            $payment->fresh(),
            $previous,
            Payment::STATUS_REFUNDED,
            'مرجوعی تارا با شماره مرجع '.$reference
        );

        $order = $payment->order()->first();
        if ($order && $order->status === Order::STATUS_PROCESSING) {
            $order->unsetRelation('payments');
            if ($order->remainingAmount() > 0) {
                $orderPrevious = $order->status;
                $order->update(['status' => Order::STATUS_PENDING]);
                $this->orderLog->statusChanged($order->fresh(), $orderPrevious, Order::STATUS_PENDING);
            }
        }

        return $payment->fresh();
    }

    /** @param  array<string, mixed>  $config */
    protected function refundToken(array $config): string
    {
        $response = Http::baseUrl(rtrim((string) $config['refund_base_url'], '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->post('/api/v1/user/login/refund', [
                'principal' => $config['refund_principal'],
                'password' => $config['refund_password'],
            ]);

        $data = $response->json();
        $token = (string) data_get($data, 'accessCode', data_get($data, 'data.accessCode', ''));

        if ($response->failed() || $token === '' || data_get($data, 'success') === false) {
            throw new RuntimeException((string) (data_get($data, 'message', data_get($data, 'data.message')) ?: 'ورود سرویس مرجوعی تارا ناموفق بود.'));
        }

        return $token;
    }

    protected function referenceNumber(Payment $payment): string
    {
        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];

        foreach (['rrn', 'data.rrn', 'channelRefNumber', 'refund.rrn'] as $path) {
            $value = data_get($raw, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return (string) ($payment->card_number ?: '');
    }
}
