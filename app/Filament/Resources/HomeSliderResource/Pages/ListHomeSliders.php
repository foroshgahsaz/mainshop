<?php

namespace App\Filament\Resources\HomeSliderResource\Pages;

use App\Filament\Resources\HomeSliderResource;
use App\Filament\Resources\Pages\AdminListRecords;
use Filament\Actions;

class ListHomeSliders extends AdminListRecords
{
    protected static string $resource = HomeSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('اسلاید جدید'),
        ];
    }
}
