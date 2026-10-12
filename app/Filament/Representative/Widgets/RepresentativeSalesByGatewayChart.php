<?php

namespace App\Filament\Representative\Widgets;

use App\Services\Representative\RepresentativeDashboardService;
use App\Support\ShopLabels;
use Filament\Widgets\ChartWidget;

class RepresentativeSalesByGatewayChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'فروش ماه جاری به تفکیک درگاه';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        if ($user === null) {
            return ['datasets' => [], 'labels' => []];
        }

        try {
            $byGateway = app(RepresentativeDashboardService::class)->salesByGateway($user);
        } catch (\Throwable $e) {
            report($e);
            $byGateway = [];
        }
        $labels = [];
        $amounts = [];

        foreach ($byGateway as $gateway => $total) {
            $labels[] = ShopLabels::paymentMethod((string) $gateway);
            $amounts[] = (int) round($total / 1000);
        }

        if ($labels === []) {
            $labels = ['بدون داده'];
            $amounts = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'مبلغ (هزار تومان)',
                    'data' => $amounts,
                    'backgroundColor' => [
                        'rgba(13, 148, 136, 0.75)',
                        'rgba(99, 102, 241, 0.75)',
                        'rgba(245, 158, 11, 0.75)',
                        'rgba(236, 72, 153, 0.75)',
                        'rgba(59, 130, 246, 0.75)',
                    ],
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
