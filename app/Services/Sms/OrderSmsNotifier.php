<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderSmsNotifier
{
    public function __construct(
        protected SmsSender $sms
    ) {}

    public function orderPlaced(Order $order): void
    {
        $this->send($order, sprintf(
            'چاپینو: سفارش %s ثبت شد. مبلغ: %s تومان',
            $order->tracking_code,
            number_format($order->final_amount)
        ));
    }

    public function orderPaid(Order $order): void
    {
        $this->send($order, sprintf(
            'چاپینو: پرداخت سفارش %s با موفقیت انجام شد.',
            $order->tracking_code
        ));
    }

    public function orderShipped(Order $order): void
    {
        $msg = sprintf('چاپینو: سفارش %s ارسال شد.', $order->tracking_code);

        if ($order->shipping_tracking_code) {
            $msg .= ' رهگیری: '.$order->shipping_tracking_code;
        }

        $this->send($order, $msg);
    }

    public function orderDelivered(Order $order): void
    {
        $this->send($order, sprintf(
            'چاپینو: سفارش %s تحویل داده شد. از خرید شما سپاسگزاریم.',
            $order->tracking_code
        ));
    }

    public function orderCanceled(Order $order): void
    {
        $this->send($order, sprintf(
            'چاپینو: سفارش %s لغو شد.',
            $order->tracking_code
        ));
    }

    protected function send(Order $order, string $message): void
    {
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        try {
            $this->sms->sendTransactional($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Order SMS failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
