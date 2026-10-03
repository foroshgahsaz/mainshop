<?php

namespace App\Filament\Resources\ProductPlantResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductPlantResource;

class ListProductPlants extends AdminListRecords
{
    protected static string $resource = ProductPlantResource::class;

    protected function listCreateActionLabel(): ?string
    {
        return 'افزودن کارخانه';
    }
}
