<?php

namespace App\Filament\Resources\SalesRegionResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\SalesRegionResource;

class ListSalesRegions extends AdminListRecords
{
    protected static string $resource = SalesRegionResource::class;

    protected function adminListIcon(): ?string
    {
        return 'fa-map-location-dot';
    }

    public static function topBarCreateLabel(): ?string
    {
        return 'افزودن منطقه';
    }
}
