<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListCoupons extends AdminListRecords
{
    protected static string $resource = CouponResource::class;
}
