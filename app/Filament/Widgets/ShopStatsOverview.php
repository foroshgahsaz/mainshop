<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayRevenue = (int) Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereDate('paid_at', today())
            ->sum('amount');
        $pendingOrders = Order::where('status', Order::STATUS_PENDING)->count();
        $monthRevenue = (int) Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $lowStock = Product::where('is_active', true)->where('stock', '<=', 5)->count();

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

    protected function getColumns(): int
    {
        return 4;
    }
}
