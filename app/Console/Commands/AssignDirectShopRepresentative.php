<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class AssignDirectShopRepresentative extends Command
{
    protected $signature = 'shop:assign-direct-shop-representative {--dry-run : فقط تعداد را نشان بده}';

    protected $description = 'نمایندهٔ پیش‌فرض (چینی بازار) را روی سفارش‌های مستقیم فروشگاه بدون representative_id ست می‌کند';

    public function handle(): int
    {
        $directId = Order::directShopRepresentativeId();

        if ($directId === null) {
            $this->components->warn('shop.checkout.direct_representative_id تنظیم نشده — کاری انجام نشد.');

            return self::SUCCESS;
        }

        $query = Order::query()
            ->whereNull('representative_id')
            ->where('status', '!=', Order::STATUS_DRAFT);

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->components->info('سفارشی برای به‌روزرسانی نیست.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("{$count} سفارش با representative_id={$directId} به‌روز می‌شوند (dry-run).");

            return self::SUCCESS;
        }

        $updated = $query->update(['representative_id' => $directId]);

        $this->components->info("{$updated} سفارش به representative_id={$directId} نسبت داده شد.");

        return self::SUCCESS;
    }
}
