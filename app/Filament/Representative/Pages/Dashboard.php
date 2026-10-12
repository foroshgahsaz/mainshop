<?php

namespace App\Filament\Representative\Pages;

use App\Filament\Representative\Widgets\RepresentativeCurrentOrdersTable;
use App\Filament\Representative\Widgets\RepresentativeDashboardInsights;
use App\Filament\Representative\Widgets\RepresentativeSalesByGatewayChart;
use App\Filament\Representative\Widgets\RepresentativeStatsOverview;
use Filament\Actions;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $layout = 'filament-panels::components.layout.representative';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'داشبورد نمایندگی';

    protected static ?string $navigationLabel = 'داشبورد';

    public function getWidgets(): array
    {
        return [
            RepresentativeStatsOverview::class,
            RepresentativeSalesByGatewayChart::class,
            RepresentativeDashboardInsights::class,
            RepresentativeCurrentOrdersTable::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('newOrder')
                ->label('سفارش جدید')
                ->icon('heroicon-o-plus')
                ->url(CreateDraftOrder::getUrl(panel: 'representative')),
        ];
    }
}
