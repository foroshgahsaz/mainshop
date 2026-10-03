<?php

namespace App\Filament\Resources\HomeSliderResource\Pages;

use App\Filament\Resources\HomeSliderResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListHomeSliders extends AdminListRecords
{
    protected static string $resource = HomeSliderResource::class;

    public static function topBarCreateLabel(): ?string
    {
        return 'اسلاید جدید';
    }
}
