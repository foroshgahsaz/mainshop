<?php

namespace App\Filament\Representative\Resources\DraftOrderResource\Pages;

use App\Filament\Representative\Pages\CreateDraftOrder;
use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

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

    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    {
        $paginator = parent::paginateTableQuery($query);
        Order::attachCatalogTemplateLabels($paginator->getCollection());

        return $paginator;
    }
}
