<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $title = 'داشبورد';

    protected static ?string $navigationLabel = 'داشبورد';

    protected static bool $shouldRegisterNavigation = false;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\ShopStatsOverview::class,
            \App\Filament\Widgets\OrdersTrendChart::class,
            \App\Filament\Widgets\OrdersMapWidget::class,
            \App\Filament\Widgets\LatestOrdersTable::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
