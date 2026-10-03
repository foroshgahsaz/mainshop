<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListCategories extends AdminListRecords
{
    protected static string $resource = CategoryResource::class;
}
