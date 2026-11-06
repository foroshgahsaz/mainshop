<?php

namespace App\Filament\Resources\ShopUrlRedirectResource\Pages;

use App\Filament\Resources\ShopUrlRedirectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShopUrlRedirects extends ListRecords
{
    protected static string $resource = ShopUrlRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('ریدایرکت جدید'),
        ];
    }
}
