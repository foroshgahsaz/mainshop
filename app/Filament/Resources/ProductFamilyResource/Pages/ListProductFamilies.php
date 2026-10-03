<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductFamilyResource;

class ListProductFamilies extends AdminListRecords
{
    protected static string $resource = ProductFamilyResource::class;

    public static function topBarCreateLabel(): ?string
    {
        return 'افزودن خانواده';
    }
}
