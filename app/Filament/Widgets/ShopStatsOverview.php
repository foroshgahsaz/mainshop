<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class ShopStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $metrics = $this->metrics();

        $todayOrders = $metrics['today_orders'];
        $todayRevenue = $metrics['today_revenue'];
        $pendingOrders = $metrics['pending_orders'];
        $monthRevenue = $metrics['month_revenue'];
        $lowStock = $metrics['low_stock'];

        return [
            Stat::make('فروش امروز', number_format($todayRevenue).' تومان')
                ->description("{$todayOrders} سفارش ثبت‌شده")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([2, 4, 3, 6, 5, 8, max(1, min(20, (int) ($todayRevenue / 100000)))]),
            Stat::make('درآمد ماه جاری', number_format($monthRevenue).' تومان')
                ->description('پرداخت‌های موفق')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
            Stat::make('سفارش‌های در انتظار', number_format($pendingOrders))
                ->description('نیاز به بررسی')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.orders.index')),
            Stat::make('موجودی کم', number_format($lowStock))
                ->description('محصولات با موجودی ≤ ۵')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.products.index')),
        ];
    }

    /** @return array{today_orders:int,today_revenue:int,pending_orders:int,month_revenue:int,low_stock:int} */
    protected function metrics(): array
    {
        try {
            return Cache::remember('admin:dashboard:shop_stats', 120, function (): array {
                return [
                    'today_orders' => Order::whereDate('created_at', today())->count(),
                    'today_revenue' => (int) Payment::query()
                        ->where('status', Payment::STATUS_SUCCESS)
                        ->whereDate('paid_at', today())
                        ->sum('amount'),
                    'pending_orders' => Order::where('status', Order::STATUS_PENDING)->count(),
                    'month_revenue' => (int) Payment::query()
                        ->where('status', Payment::STATUS_SUCCESS)
                        ->where('paid_at', '>=', now()->startOfMonth())
                        ->sum('amount'),
                    'low_stock' => Product::where('is_active', true)->where('stock', '<=', 5)->count(),
                ];
            });
        } catch (\Throwable $e) {
            report($e);

            return ['today_orders' => 0, 'today_revenue' => 0, 'pending_orders' => 0, 'month_revenue' => 0, 'low_stock' => 0];
        }
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
