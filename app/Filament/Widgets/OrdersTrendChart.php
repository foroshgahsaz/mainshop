<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersTrendChart extends ChartWidget
{
    protected static ?string $heading = 'روند فروش ۳۰ روز اخیر';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $i) => now()->subDays($i)->startOfDay());

        $ordersByDay = Order::query()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $revenueByDay = Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->where('paid_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = $days->map(fn (Carbon $d) => $d->format('m/d'))->values()->all();
        $orderCounts = $days->map(fn (Carbon $d) => (int) ($ordersByDay[$d->toDateString()] ?? 0))->values()->all();
        $revenues = $days->map(fn (Carbon $d) => (int) (($revenueByDay[$d->toDateString()] ?? 0) / 1000))->values()->all();

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
