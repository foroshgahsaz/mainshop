<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListOrders extends AdminListRecords
{
    protected static string $resource = OrderResource::class;

    protected function adminListIcon(): ?string
    {
        return 'fa-shopping-bag';
    }
}
