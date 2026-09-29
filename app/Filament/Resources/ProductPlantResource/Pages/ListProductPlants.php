<?php

namespace App\Filament\Resources\ProductPlantResource\Pages;

use App\Filament\Resources\ProductPlantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductPlants extends ListRecords
{
    protected static string $resource = ProductPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن کارخانه'),
        ];
    }
}
