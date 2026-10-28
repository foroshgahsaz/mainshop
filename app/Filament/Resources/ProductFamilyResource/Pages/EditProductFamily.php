<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\ProductFamilyResource;
use App\Filament\Resources\Pages\EditRecord;
use App\Services\Cache\ShopCacheService;
use Filament\Actions;

class EditProductFamily extends EditRecord
{
    protected static string $resource = ProductFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function afterSave(): void
    {
        app(ShopCacheService::class)->forgetHome();
    }
}
