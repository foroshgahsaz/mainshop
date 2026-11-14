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
        if (! AdminAccess::canCreateOrdersInAdmin()) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('ایجاد سفارش')
                ->icon('heroicon-o-plus'),
        ];
    }
}
