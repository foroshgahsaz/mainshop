<?php

namespace App\Filament\Resources\FreightCarrierResource\Pages;

use App\Filament\Resources\FreightCarrierResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListFreightCarriers extends AdminListRecords
{
    protected static string $resource = FreightCarrierResource::class;

    public static function topBarCreateLabel(): ?string
    {
        return 'افزودن باربری';
    }
}
