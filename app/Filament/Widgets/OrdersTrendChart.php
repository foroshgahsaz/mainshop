<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class OrdersTrendChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'روند فروش ۳۰ روز اخیر';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        try {
            return $this->buildData();
        } catch (\Throwable $e) {
            report($e);

            return ['datasets' => [], 'labels' => []];
        }
    }

    protected function buildData(): array
    {
        $scopeKey = AdminAccess::isLimitedStaffOnly() ? 'staff:'.(int) auth()->id() : 'global';

        return Cache::remember('admin:dashboard:orders_trend_chart:'.$scopeKey, 300, function (): array {
            $days = collect(range(29, 0))->map(fn (int $i) => now()->subDays($i)->startOfDay());

            $ordersQuery = Order::query()
                ->where('created_at', '>=', now()->subDays(29)->startOfDay());
            $paymentsQuery = Payment::query()
                ->where('status', Payment::STATUS_SUCCESS)
                ->where('paid_at', '>=', now()->subDays(29)->startOfDay());

            if (AdminAccess::isLimitedStaffOnly()) {
                $actorId = (int) auth()->id();
                $regions = app(SalesRegionService::class);
                $ordersQuery = $regions->scopeOrdersManagedBy($ordersQuery, $actorId);
                $repIds = $regions->managedRepresentativeIds($actorId);
                $paymentsQuery = $paymentsQuery->whereHas(
                    'order',
                    fn ($q) => $q->whereIn('representative_id', $repIds ?: [0]),
                );
            }

            $ordersByDay = $ordersQuery
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $revenueByDay = $paymentsQuery
                ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $labels = $days->map(fn ($d) => Carbon::parse($d)->shopJalali('m/d'))->values()->all();
            $orderCounts = $days->map(fn ($d) => (int) ($ordersByDay[Carbon::parse($d)->toDateString()] ?? 0))->values()->all();
            $revenues = $days->map(fn ($d) => (int) (($revenueByDay[Carbon::parse($d)->toDateString()] ?? 0) / 1000))->values()->all();

            return [
                'datasets' => [
                    [
                        'label' => 'تعداد سفارش',
                        'data' => $orderCounts,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                        'tension' => 0.35,
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'درآمد (هزار تومان)',
                        'data' => $revenues,
                        'borderColor' => '#f59e0b',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.08)',
                        'fill' => true,
                        'tension' => 0.35,
                        'yAxisID' => 'y1',
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => true],
            ],
        ];
    }
}
