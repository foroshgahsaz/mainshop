<?php

namespace App\Filament\Resources\ShopUrlRedirectResource\Pages;

use App\Filament\Resources\ShopUrlRedirectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShopUrlRedirect extends CreateRecord
{
    protected static string $resource = ShopUrlRedirectResource::class;

    protected static ?string $title = 'ریدایرکت جدید';
}
