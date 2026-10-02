<?php

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\Pages\AdminListRecords;
use Filament\Actions;

class ListMenuItems extends AdminListRecords
{
    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن آیتم'),
        ];
    }
}
