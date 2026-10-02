<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductResource;
use Filament\Actions;

class ListProducts extends AdminListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
