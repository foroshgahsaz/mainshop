<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'shop:expire-pending-orders {--minutes= : دقایق انتظار قبل از لغو}';

    protected $description = 'لغو سفارش‌های آنلاین پرداخت‌نشده و آزادسازی موجودی رزرو شده';

    public function handle(OrderService $orders): int
    {
        $minutes = (int) ($this->option('minutes') ?: config('shop.checkout.unpaid_ttl_minutes', 60));
        $cutoff = now()->subMinutes($minutes);

        $pending = Order::query()
            ->where('payment_method', 'online')
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'success'))
            ->get();

        $expired = 0;

        foreach ($pending as $order) {
            try {
                $orders->expireUnpaid($order);
                $expired++;
            } catch (\RuntimeException) {
                continue;
            }
        }

        $this->info("{$expired} سفارش منقضی لغو شد.");

        return self::SUCCESS;
    }
}
