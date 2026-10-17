<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Str;
use RuntimeException;

class DigiPayRefundService
{
    public function __construct(
        protected SettingsService $settings,
        protected DigiPayGateway $gateway,
        protected PaymentActivityLogger $paymentLog,
        protected OrderActivityLogger $orderLog,
        protected PaymentAuditLogger $audit,
    ) {}

    public function refund(Payment $payment, ?int $shopAmount = null): Payment
    {
        if ($payment->gateway !== 'digipay') {
            throw new RuntimeException('این پرداخت مربوط به دیجی‌پی نیست.');
        }

        if ($payment->status !== Payment::STATUS_SUCCESS) {
            throw new RuntimeException('فقط پرداخت موفق دیجی‌پی قابل لغو/عودت است.');
        }

        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];
        if (isset($raw['digipay_refund'])) {
            throw new RuntimeException('برای این پرداخت قبلاً درخواست عودت دیجی‌پی ثبت شده است.');
        }

        $saleTrackingCode = $this->saleTrackingCode($payment);
        if ($saleTrackingCode === '') {
            throw new RuntimeException('کد پیگیری خرید دیجی‌پی (trackingCode) یافت نشد.');
        }

        $ticketType = $this->ticketType($payment);
        $config = $this->settings->digipay();
        $amount = $shopAmount ?? $payment->amount;
        if ($amount <= 0 || $amount > $payment->amount) {
            throw new RuntimeException('مبلغ عودت نامعتبر است.');
        }

        $amountRial = AmountConverter::toGateway(
            $amount,
            (string) ($config['amount_unit'] ?? AmountConverter::UNIT_TOMAN)
        );

        $refundProviderId = $payment->tracking_code.'-refund-'.Str::lower(Str::random(8));

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_REQUEST,
            'digipay_refund_request',
            'درخواست عودت خرید دیجی‌پی ارسال شد.',
            [
                'saleTrackingCode' => $saleTrackingCode,
                'ticketType' => $ticketType,
                'amountRial' => $amountRial,
                'refundProviderId' => $refundProviderId,
            ]
        );

        $data = $this->gateway->submitRefund($ticketType, $refundProviderId, $amountRial, $saleTrackingCode);
        $status = (int) data_get($data, 'result.status', -1);

        if ($status !== 0) {
            $message = (string) data_get($data, 'result.message', 'عودت دیجی‌پی ناموفق بود.');

            $this->audit->failure(
                $payment,
                PaymentAuditStep::GATEWAY_RESPONSE,
                'digipay_refund_failed',
                $message,
                null,
                ['response' => $data]
            );

            throw new RuntimeException($message);
        }

        $inquiryData = null;

        try {
            $inquiryData = $this->gateway->inquireRefund($refundProviderId, $ticketType);
        } catch (\Throwable) {
            // Inquiry is best-effort; refund submit success is primary.
        }

        $previous = $payment->status;
        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'raw_response' => array_merge($raw, [
                'digipay_refund' => $data,
                'digipay_refund_inquiry' => $inquiryData,
                'digipay_refund_provider_id' => $refundProviderId,
            ]),
        ]);

        $payment = $payment->fresh();
        $refundTracking = (string) data_get($data, 'trackingCode', '');

        $this->paymentLog->statusChanged(
            $payment,
            $previous,
            Payment::STATUS_REFUNDED,
            'عودت دیجی‌پی'.($refundTracking !== '' ? ' (کد: '.$refundTracking.')' : '')
        );

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_RESPONSE,
            'digipay_refund_success',
            'عودت دیجی‌پی با موفقیت ثبت شد.',
            ['trackingCode' => $refundTracking]
        );

        $order = $payment->order;
        if ($order instanceof Order) {
            $this->orderLog->system(
                $order->fresh(),
                'عودت/لغو دیجی‌پی از پنل ادمین ثبت شد.'.($refundTracking !== '' ? ' کد پیگیری عودت: '.$refundTracking : ''),
                'digipay_refund'
            );

            if (! $order->payments()->where('status', Payment::STATUS_SUCCESS)->exists()) {
                $orderPrevious = $order->status;
                if (in_array($order->status, [Order::STATUS_PROCESSING, Order::STATUS_WAREHOUSE_PROCESSING], true)) {
                    $order->update(['status' => Order::STATUS_PENDING]);
                    $this->orderLog->statusChanged($order->fresh(), $orderPrevious, Order::STATUS_PENDING, 'پس از عودت دیجی‌پی');
                }
            }
        }

        return $payment;
    }

    protected function saleTrackingCode(Payment $payment): string
    {
        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];

        foreach ([
            data_get($raw, 'trackingCode'),
            $payment->transaction_id,
            $payment->card_number,
            data_get($raw, 'digipay_tracking_code'),
        ] as $candidate) {
            $code = trim((string) $candidate);
            if ($code !== '') {
                return $code;
            }
        }

        return '';
    }

    protected function ticketType(Payment $payment): string
    {
        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];

        foreach ([
            data_get($raw, 'digipay_type'),
            data_get($raw, 'type'),
            data_get($raw, 'digipay_ticket_type'),
        ] as $candidate) {
            $type = trim((string) $candidate);
            if ($type !== '') {
                return $type;
            }
        }

        $config = $this->settings->digipay();

        return (string) ($config['ticket_type'] ?? 11);
    }
}
