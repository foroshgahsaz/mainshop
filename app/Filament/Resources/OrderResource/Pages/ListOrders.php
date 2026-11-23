<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\Pages\AdminListRecords;
use App\Support\AdminAccess;
use Filament\Actions;

class ListOrders extends AdminListRecords
{
    protected static string $resource = OrderResource::class;

    protected function adminListIcon(): ?string
    {
        return 'fa-shopping-bag';
    }

    protected function extraListHeaderActions(): array
    {
        $actions = [];

        if (AdminAccess::canAccessAdminResource(OrderResource::class)) {
            $actions[] = Actions\Action::make('exportExcel')
                ->label('خروجی اکسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('filament.admin.orders.export', $this->orderExportQueryParameters()))
                ->openUrlInNewTab();
        }

        if (AdminAccess::canCreateOrdersInAdmin()) {
            $actions[] = Actions\CreateAction::make()
                ->label('ایجاد سفارش')
                ->icon('heroicon-o-plus');
        }

        return $actions;
    }

    /**
     * @return array<string, string>
     */
    protected function orderExportQueryParameters(): array
    {
        $filters = $this->tableFilters ?? [];
        $params = [];

        $status = data_get($filters, 'status.value');
        if (is_string($status) && $status !== '') {
            $params['status'] = $status;
        }

        $gateway = data_get($filters, 'payment_gateway.value');
        if (is_string($gateway) && $gateway !== '') {
            $params['payment_gateway'] = $gateway;
        }

        $trashed = data_get($filters, 'trashed.value');
        if (is_string($trashed) && $trashed !== '') {
            $params['trashed'] = $trashed;
        }

        return $params;
    }
}
