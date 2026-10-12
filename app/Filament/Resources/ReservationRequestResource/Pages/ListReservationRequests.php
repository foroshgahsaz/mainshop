<?php

namespace App\Filament\Resources\ReservationRequestResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ReservationRequestResource;

class ListReservationRequests extends AdminListRecords
{
    protected static string $resource = ReservationRequestResource::class;

    protected function adminListIcon(): ?string
    {
        return 'fa-inbox';
    }

    protected function adminListTitle(): ?string
    {
        return 'درخواست‌های رزرو مجدد';
    }
}
