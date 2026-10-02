<?php

namespace App\Services\Sms;

use App\Models\Order;
use App\Models\Payment;

class OrderSmsNotifier
{
    public function __construct(
        protected TransactionalSmsDispatcher $transactional,
    ) {}

    public function orderPlaced(Order $order): void
    {
        $this->transactional->orderPlaced($order);
    }

    public function orderPaid(Order $order, ?Payment $payment = null): void
    {
        $payment ??= $order->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->latest()
            ->first();

        if ($payment) {
            $this->transactional->orderPaid($order, $payment);
        }
    }

    public function paymentFailed(Order $order, ?Payment $payment = null): void
    {
        $this->transactional->paymentFailed($order, $payment);
    }

    public function paymentPartialRemaining(Order $order, int $remainingAmount): void
    {
        $this->transactional->paymentPartialRemaining($order, $remainingAmount);
    }

    public function orderShipped(Order $order): void
    {
        $this->transactional->orderShipped($order);
    }

    public function orderDelivered(Order $order): void
    {
        $this->transactional->orderDelivered($order);
    }

    public function orderCanceled(Order $order): void
    {
        $this->transactional->orderCanceled($order);
    }

    public function orderExpiredUnpaid(Order $order): void
    {
        $this->transactional->orderExpiredUnpaid($order);
    }

    public function proformaCreated(Order $order): void
    {
        $this->transactional->proformaCreated($order);
    }

    public function proformaReservationExpired(Order $order): void
    {
        $this->transactional->proformaReservationExpired($order);
    }

    public function proformaReservationExtended(Order $order): void
    {
        $this->transactional->proformaReservationExtended($order);
    }
}
