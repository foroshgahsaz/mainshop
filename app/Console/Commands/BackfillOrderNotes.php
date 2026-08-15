<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use App\Services\Payment\PaymentActivityLogger;
use App\Support\ShopLabels;
use Illuminate\Console\Command;

class BackfillOrderNotes extends Command
{
    protected $signature = 'shop:backfill-order-notes';

    protected $description = 'ساخت یادداشت اولیه برای سفارش‌ها و پرداخت‌های موجود بدون تاریخچه';

    public function handle(OrderActivityLogger $orderLog, PaymentActivityLogger $paymentLog): int
    {
        $orders = Order::with('user')->whereDoesntHave('notes')->get();

        foreach ($orders as $order) {
            if ($order->user) {
                $orderLog->orderCreated($order, $order->user);
            } else {
                $orderLog->system(
                    $order,
                    sprintf('سفارش ثبت شد. مبلغ: %s', ShopLabels::formatMoney($order->final_amount)),
                    'order_created'
                );
            }

            if ($order->shipped_at) {
                $orderLog->system($order, 'سفارش ارسال شد.', 'status_changed', ['to' => 'shipped']);
            }

            if ($order->delivered_at) {
                $orderLog->system($order, 'سفارش تحویل داده شد.', 'status_changed', ['to' => 'delivered']);
            }
        }

        $payments = Payment::whereDoesntHave('notes')->get();

        foreach ($payments as $payment) {
            $paymentLog->created($payment);

            if ($payment->status !== Payment::STATUS_PENDING) {
                $paymentLog->statusChanged($payment, Payment::STATUS_PENDING, $payment->status, 'بازسازی تاریخچه');
            }
        }

        $this->info("✅ {$orders->count()} سفارش و {$payments->count()} پرداخت backfill شد.");

        return self::SUCCESS;
    }
}
