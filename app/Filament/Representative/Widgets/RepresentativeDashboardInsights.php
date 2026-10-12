<?php

namespace App\Filament\Representative\Widgets;

use App\Services\Representative\RepresentativeDashboardService;
use App\Support\ShopFormatter;
use Filament\Widgets\Widget;

class RepresentativeDashboardInsights extends Widget
{
    protected static string $view = 'filament.representative.widgets.dashboard-insights';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?string $pollingInterval = null;

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $user = auth()->user();
        if ($user === null) {
            return [
                'metrics' => [],
                'gateways' => [],
                'templates' => [],
            ];
        }

        $service = app(RepresentativeDashboardService::class);

        return [
            'metrics' => $service->summaryMetrics($user),
            'gateways' => $service->gatewayBreakdownRows($user),
            'templates' => collect($service->salesByCatalogTemplate($user))
                ->map(fn (int $total, string $label): array => [
                    'label' => $label,
                    'total' => $total,
                    'total_formatted' => ShopFormatter::money($total),
                ])
                ->values()
                ->all(),
        ];
    }
}
