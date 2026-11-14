<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Services\Catalog\BankdarCatalogService;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
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
            $scopeKey = 'global';
            if (AdminAccess::isLimitedStaffOnly()) {
                $scopeKey = 'staff:'.(int) auth()->id();
            }

            return Cache::remember('admin:dashboard:shop_stats:'.$scopeKey, 120, function (): array {
                $orders = Order::query();
                $payments = Payment::query()
                    ->where('status', Payment::STATUS_SUCCESS);
                $products = Product::query()->where('is_active', true)->where('stock', '<=', 5);

                if (AdminAccess::isLimitedStaffOnly()) {
                    $actorId = (int) auth()->id();
                    $regions = app(SalesRegionService::class);
                    $orders = $regions->scopeOrdersManagedBy($orders, $actorId);
                    $repIds = $regions->managedRepresentativeIds($actorId);
                    $payments = $payments->whereHas('order', fn ($q) => $q->whereIn('representative_id', $repIds ?: [0]));
                    $products = app(BankdarCatalogService::class)->scopeProductsForActor($products);
                }

                return [
                    'today_orders' => (clone $orders)->whereDate('created_at', today())->count(),
                    'today_revenue' => (int) (clone $payments)->whereDate('paid_at', today())->sum('amount'),
                    'pending_orders' => (clone $orders)->where('status', Order::STATUS_PENDING)->count(),
                    'month_revenue' => (int) (clone $payments)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                    'low_stock' => $products->count(),
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
