<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\ProductFamilyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductFamily extends EditRecord
{
    protected static string $resource = ProductFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
