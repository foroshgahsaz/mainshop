<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use App\Filament\Resources\Pages\AdminListRecords;
use Filament\Actions;

class ListPages extends AdminListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('صفحه جدید'),
        ];
    }
}
