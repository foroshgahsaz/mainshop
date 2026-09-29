<?php

namespace App\Filament\Resources\ProductPlantResource\Pages;

use App\Filament\Resources\ProductPlantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductPlant extends EditRecord
{
    protected static string $resource = ProductPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
