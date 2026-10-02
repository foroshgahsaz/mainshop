<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductFamilyResource;
use Filament\Actions;

class ListProductFamilies extends AdminListRecords
{
    protected static string $resource = ProductFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن خانواده'),
        ];
    }
}
