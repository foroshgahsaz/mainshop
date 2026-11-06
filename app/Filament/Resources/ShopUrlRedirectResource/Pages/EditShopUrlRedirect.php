<?php

namespace App\Filament\Resources\ShopUrlRedirectResource\Pages;

use App\Filament\Resources\ShopUrlRedirectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShopUrlRedirect extends EditRecord
{
    protected static string $resource = ShopUrlRedirectResource::class;

    protected static ?string $title = 'ویرایش ریدایرکت';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }
}
