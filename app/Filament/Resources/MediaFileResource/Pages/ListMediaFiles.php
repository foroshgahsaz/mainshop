<?php

namespace App\Filament\Resources\MediaFileResource\Pages;

use App\Filament\Resources\MediaFileResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListMediaFiles extends AdminListRecords
{
    protected static string $resource = MediaFileResource::class;

    protected static ?string $title = 'مدیریت فایل‌ها';
}
