<?php

namespace App\Filament\Resources\ProductTemplateResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductTemplateResource;

class ListProductTemplates extends AdminListRecords
{
    protected static string $resource = ProductTemplateResource::class;

    public static function topBarCreateLabel(): ?string
    {
        return 'افزودن قالب';
    }
}
