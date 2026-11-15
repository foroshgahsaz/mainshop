<?php

namespace App\Filament\Support;

use Filament\Forms;

final class AdminShopPreviewToggle
{
    public static function make(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('admin_shop_preview')
            ->label('نمایش برای مدیر / تست درگاه')
            ->helperText('مشتری عادی نمی‌بیند. فقط مدیر سیستم، مدیر فروش، یا کاربری با تیک «تست درگاه» (در ویرایش کاربر) پس از ورود به فروشگاه می‌بیند.')
            ->default(false)
            ->inline(false);
    }
}
