<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\PaymentResource;

class ListPayments extends AdminListRecords
{
    protected static string $resource = PaymentResource::class;
}
