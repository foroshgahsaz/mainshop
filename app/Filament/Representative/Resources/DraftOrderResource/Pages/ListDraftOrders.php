<?php

namespace App\Filament\Representative\Resources\DraftOrderResource\Pages;

use App\Filament\Representative\Pages\CreateDraftOrder;
use App\Filament\Representative\Resources\DraftOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDraftOrders extends ListRecords
{
    protected static string $layout = 'filament-panels::components.layout.representative';

    protected static string $resource = DraftOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label('سفارش جدید')
                ->url(CreateDraftOrder::getUrl())
                ->icon('heroicon-o-plus'),
        ];
    }
}
