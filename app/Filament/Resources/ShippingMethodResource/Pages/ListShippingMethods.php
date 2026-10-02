<?php

namespace App\Filament\Resources\ShippingMethodResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ShippingMethodResource;
use Filament\Actions;

class ListShippingMethods extends AdminListRecords
{
    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
