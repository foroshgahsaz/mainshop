<?php

namespace App\Filament\Resources\OtpMessageResource\Pages;

use App\Filament\Resources\OtpMessageResource;
use App\Filament\Resources\Pages\AdminListRecords;

class ListOtpMessages extends AdminListRecords
{
    protected static string $resource = OtpMessageResource::class;

    protected static ?string $title = 'مدیریت OTP';

    protected function adminListIcon(): ?string
    {
        return 'heroicon-o-key';
    }

    protected function adminListTitle(): ?string
    {
        return 'OTPهای ارسال‌شده';
    }

    protected function adminListSection(): ?string
    {
        return 'کاربران';
    }
}
