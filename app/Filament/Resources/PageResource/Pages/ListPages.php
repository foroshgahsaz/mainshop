<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListPages extends AdminListRecords
{
    protected static string $resource = PageResource::class;

    protected function listCreateActionLabel(): ?string
    {
        return 'صفحه جدید';
    }
}
