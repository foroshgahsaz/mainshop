<?php

namespace App\Filament\Resources\ProductTemplateResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductTemplateResource;
use Filament\Actions;

class ListProductTemplates extends AdminListRecords
{
    protected static string $resource = ProductTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن قالب'),
        ];
    }
}
