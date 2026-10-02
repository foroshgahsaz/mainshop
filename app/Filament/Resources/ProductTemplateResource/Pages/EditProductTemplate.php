<?php

namespace App\Filament\Resources\ProductTemplateResource\Pages;

use App\Filament\Resources\ProductTemplateResource;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;

class EditProductTemplate extends EditRecord
{
    protected static string $resource = ProductTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
