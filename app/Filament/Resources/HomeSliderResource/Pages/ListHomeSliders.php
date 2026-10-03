<?php

namespace App\Filament\Resources\HomeSliderResource\Pages;

use App\Filament\Resources\HomeSliderResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListHomeSliders extends AdminListRecords
{
    protected static string $resource = HomeSliderResource::class;

    protected function listCreateActionLabel(): ?string
    {
        return 'اسلاید جدید';
    }
}
