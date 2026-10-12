<?php

namespace App\Filament\Representative\Widgets;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Services\Representative\RepresentativeDashboardService;
use App\Support\ShopFormatter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RepresentativeStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $user = auth()->user();
        if ($user === null) {
            return [];
        }

        $metrics = app(RepresentativeDashboardService::class)->summaryMetrics($user);

        return [
            Stat::make('فروش ماه '.$metrics['month_label'], ShopFormatter::money($metrics['month_sales']))
                ->description($metrics['month_paid_orders_count'].' سفارش با پرداخت موفق')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
            Stat::make('فروش امروز', ShopFormatter::money($metrics['today_sales']))
                ->description('پرداخت‌های موفق امروز')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('سفارش‌های جاری', (string) $metrics['active_orders_count'])
                ->description('پیش‌نویس تا در حال پردازش')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info')
                ->url(DraftOrderResource::getUrl('index')),
            Stat::make('پیش‌فاکتور در انتظار پرداخت', (string) $metrics['proforma_unpaid_count'])
                ->description(
                    $metrics['expiring_reservations_count'] > 0
                        ? $metrics['expiring_reservations_count'].' رزرو در ۴۸ ساعت آینده منقضی می‌شود'
                        : 'همه رزروها در مهلت'
                )
                ->descriptionIcon('heroicon-m-clock')
                ->color($metrics['proforma_unpaid_count'] > 0 ? 'warning' : 'gray')
                ->url(DraftOrderResource::getUrl('index')),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
