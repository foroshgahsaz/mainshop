<?php

namespace App\Filament\Resources\FreightCarrierResource\Pages;

use App\Filament\Resources\FreightCarrierResource;
use App\Filament\Resources\Pages\AdminListRecords;
use Filament\Actions;

class ListFreightCarriers extends AdminListRecords
{
    protected static string $resource = FreightCarrierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن باربری'),
        ];
    }
}
