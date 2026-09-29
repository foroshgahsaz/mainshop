<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\ProductFamilyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductFamilies extends ListRecords
{
    protected static string $resource = ProductFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن خانواده'),
        ];
    }
}
