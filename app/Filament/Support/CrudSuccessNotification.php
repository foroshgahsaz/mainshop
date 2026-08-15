<?php

namespace App\Filament\Support;

use Filament\Notifications\Notification;

class CrudSuccessNotification
{
    public static function created(): Notification
    {
        return Notification::make()
            ->success()
            ->title('ذخیره شد')
            ->body('رکورد با موفقیت ایجاد شد.')
            ->icon('heroicon-o-check-circle');
    }

    public static function saved(): Notification
    {
        return Notification::make()
            ->success()
            ->title('ذخیره شد')
            ->body('تغییرات با موفقیت ذخیره شد.')
            ->icon('heroicon-o-check-circle');
    }

    public static function deleted(): Notification
    {
        return Notification::make()
            ->success()
            ->title('حذف شد')
            ->body('رکورد با موفقیت حذف شد.')
            ->icon('heroicon-o-check-circle');
    }
}
