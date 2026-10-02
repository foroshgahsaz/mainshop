<?php

namespace App\Filament\Resources\ProductReviewResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductReviewResource;

class ListProductReviews extends AdminListRecords
{
    protected static string $resource = ProductReviewResource::class;
}
