<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Cart\StockService;
use App\Services\Order\OrderActivityLogger;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(
        protected ZarinpalGateway $zarinpalGateway,
        protected PaymentActivityLogger $paymentLog,
        protected OrderActivityLogger $orderLog,
        protected StockService $stockService,
    ) {}

    public function gateway(): PaymentGatewayInterface
    {
        return match (config('payment.default')) {
            'zarinpal' => $this->zarinpalGateway,
            default => throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.'),
        };
    }

    public function createForOrder(Order $order): Payment
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $order->final_amount,
            'gateway' => config('payment.default'),
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => strtoupper(Str::random(12)),
        ]);

        $this->paymentLog->created($payment);
        $this->orderLog->paymentLinked($payment->order, $payment->tracking_code, $payment->status, 'در انتظار پرداخت در درگاه');

        return $payment;
    }

    public function initiate(Payment $payment, Order $order): string
    {
        $this->stockService->assertOrderAvailable($order);

        return $this->gateway()->initiate($payment, $order);
    }

    public function verify(Payment $payment, string $authority, string $status): Payment
    {
        return $this->gateway()->verify($payment, $authority, $status);
    }
}
