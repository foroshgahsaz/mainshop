<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected PaymentGatewayCatalog $catalog,
        protected PaymentActivityLogger $paymentLog,
        protected OrderActivityLogger $orderLog,
    ) {}

    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        return $this->gateways->driver($name);
    }

    public function createForOrder(Order $order, ?string $gateway = null): Payment
    {
        $remaining = $order->remainingAmount();

        if ($remaining <= 0) {
            throw new RuntimeException('این سفارش تسویه شده است.');
        }

        $gatewayName = $gateway ?: config('payment.default');
        $this->catalog->assertEnabled($gatewayName);

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $remaining,
            'gateway' => $gatewayName,
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => strtoupper(Str::random(12)),
        ]);

        $this->paymentLog->created($payment);
        $this->orderLog->paymentLinked($payment->order, $payment->tracking_code, $payment->status, 'در انتظار پرداخت در درگاه');

        return $payment;
    }

    public function initiate(Payment $payment, Order $order): string
    {
        if (! $order->stock_reserved) {
            throw new RuntimeException('موجودی این سفارش رزرو نشده است.');
        }

        if ($order->remainingAmount() <= 0) {
            throw new RuntimeException('این سفارش تسویه شده است.');
        }

        $this->catalog->assertEnabled($payment->gateway);

        return $this->gateway($payment->gateway)->initiate($payment, $order);
    }

    public function verify(Payment $payment, string $authority, string $status): Payment
    {
        $fresh = $payment->fresh();

        if ($fresh && $fresh->status === Payment::STATUS_SUCCESS) {
            return $fresh;
        }

        $result = $this->gateway($payment->gateway)->verify($payment, $authority, $status);

        return DB::transaction(function () use ($payment, $result) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Payment::STATUS_SUCCESS) {
                return $locked;
            }

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
            $alreadyPaid = (int) $order->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->where('id', '!=', $locked->id)
                ->sum('amount');
            $remaining = max(0, (int) $order->final_amount - $alreadyPaid);

            if ($remaining <= 0) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_CANCELED,
                    'raw_response' => is_array($result->raw) ? $result->raw : $locked->raw_response,
                ]);

                $this->paymentLog->statusChanged(
                    $locked->fresh(),
                    $previous,
                    Payment::STATUS_CANCELED,
                    'پرداخت تکراری؛ سفارش قبلاً تسویه شده است'
                );

                return $locked->fresh();
            }

            if ($result->canceled) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_CANCELED,
                    'raw_response' => $result->raw,
                ]);

                $this->paymentLog->statusChanged($locked->fresh(), $previous, Payment::STATUS_CANCELED, $result->message);
                $this->orderLog->paymentLinked($order, $locked->tracking_code, Payment::STATUS_CANCELED);

                return $locked->fresh();
            }

            if (! $result->successful) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_FAILED,
                    'raw_response' => $result->raw,
                ]);

                $this->paymentLog->statusChanged($locked->fresh(), $previous, Payment::STATUS_FAILED, $result->message);
                $this->orderLog->paymentLinked($order, $locked->tracking_code, Payment::STATUS_FAILED);

                return $locked->fresh();
            }

            $captured = min($result->paidAmount ?? $locked->amount, $remaining);

            if ($captured <= 0) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_CANCELED,
                    'raw_response' => is_array($result->raw) ? $result->raw : $locked->raw_response,
                ]);

                $this->paymentLog->statusChanged(
                    $locked->fresh(),
                    $previous,
                    Payment::STATUS_CANCELED,
                    'مبلغ قابل اعمال روی سفارش صفر است'
                );

                return $locked->fresh();
            }

            $previous = $locked->status;
            $locked->update([
                'status' => Payment::STATUS_SUCCESS,
                'amount' => $captured,
                'paid_at' => now(),
                'card_number' => $result->cardPan,
                'raw_response' => $result->raw,
            ]);

            $locked = $locked->fresh();
            $orderPrevious = $order->status;
            $paidInFull = ($alreadyPaid + $captured) >= (int) $order->final_amount;
            $partialNote = $paidInFull
                ? 'پرداخت آنلاین موفق؛ سفارش تسویه شد'
                : 'پرداخت جزئی ثبت شد. مانده: '.number_format((int) $order->final_amount - $alreadyPaid - $captured).' تومان';

            if ($paidInFull && $order->status === Order::STATUS_PENDING) {
                $order->update(['status' => Order::STATUS_PROCESSING]);
            }

            $card = $locked->card_number ? ' مرجع: '.$locked->card_number : '';
            $this->paymentLog->statusChanged($locked, $previous, Payment::STATUS_SUCCESS, 'پرداخت تأیید شد.'.$card);
            $this->orderLog->paymentLinked($order->fresh(), $locked->tracking_code, Payment::STATUS_SUCCESS, $partialNote);

            if ($paidInFull && $orderPrevious !== Order::STATUS_PROCESSING) {
                $this->orderLog->statusChanged($order->fresh(), $orderPrevious, Order::STATUS_PROCESSING);
            }

            return $locked;
        });
    }
}
