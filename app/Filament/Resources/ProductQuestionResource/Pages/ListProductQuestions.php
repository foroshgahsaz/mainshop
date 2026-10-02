<?php

namespace App\Filament\Resources\ProductQuestionResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductQuestionResource;

class ListProductQuestions extends AdminListRecords
{
    protected static string $resource = ProductQuestionResource::class;
}
