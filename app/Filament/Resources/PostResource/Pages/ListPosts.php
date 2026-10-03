<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\PostResource;

class ListPosts extends AdminListRecords
{
    protected static string $resource = PostResource::class;
}
