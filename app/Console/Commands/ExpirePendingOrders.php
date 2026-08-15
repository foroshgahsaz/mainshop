<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Order\OrderActivityLogger;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'shop:expire-pending-orders {--hours=24 : ساعات انتظار قبل از لغو}';

    protected $description = 'لغو سفارش‌های آنلاین پرداخت‌نشده قدیمی';

    public function handle(OrderActivityLogger $logger): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $orders = Order::query()
            ->where('payment_method', 'online')
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'success'))
            ->get();

        foreach ($orders as $order) {
            $order->update(['status' => Order::STATUS_CANCELED]);
            $logger->system($order, "سفارش به‌دلیل عدم پرداخت پس از {$hours} ساعت لغو شد.", 'auto_canceled');
        }

        $this->info("✅ {$orders->count()} سفارش منقضی لغو شد.");

        return self::SUCCESS;
    }
}
