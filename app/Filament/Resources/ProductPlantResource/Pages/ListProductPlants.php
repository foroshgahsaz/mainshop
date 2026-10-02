<?php

namespace App\Filament\Resources\ProductPlantResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductPlantResource;
use Filament\Actions;

class ListProductPlants extends AdminListRecords
{
    protected static string $resource = ProductPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن کارخانه'),
        ];
    }
}
