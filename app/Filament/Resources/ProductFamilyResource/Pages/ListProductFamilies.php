<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductFamilyResource;

class ListProductFamilies extends AdminListRecords
{
    protected static string $resource = ProductFamilyResource::class;

    protected function listCreateActionLabel(): ?string
    {
        return 'افزودن خانواده';
    }
}
