<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListMenuItems extends AdminListRecords
{
    protected static string $resource = MenuItemResource::class;

    public static function topBarCreateLabel(): ?string
    {
        return 'افزودن آیتم';
    }
}
