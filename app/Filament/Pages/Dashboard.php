<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Widgets\LatestOrdersTable;
use App\Filament\Widgets\OrdersMapWidget;
use App\Filament\Widgets\OrdersTrendChart;
use App\Filament\Widgets\ShopStatsOverview;
use App\Support\AdminAccess;
use Filament\Actions;
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
            ShopStatsOverview::class,
            OrdersTrendChart::class,
            OrdersMapWidget::class,
            LatestOrdersTable::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        if (! AdminAccess::isSalesManagerOnly()) {
            return [];
        }

        return [
            Actions\Action::make('newRepresentative')
                ->label('افزودن نماینده')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url(UserResource::getUrl('create')),
        ];
    }
}
