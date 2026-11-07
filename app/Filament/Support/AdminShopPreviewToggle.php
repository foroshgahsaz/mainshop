<?php

namespace App\Filament\Support;

use Filament\Forms;

final class AdminShopPreviewToggle
{
    public static function make(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('admin_shop_preview')
            ->label('نمایش برای مدیر')
            ->helperText('فقط مدیر سیستم و مدیر فروش (با ورود به سایت) این مورد را در فروشگاه می‌بینند؛ مشتری نمی‌بیند. با «فعال» روشن یا خاموش کار می‌کند.')
            ->default(false)
            ->inline(false);
    }
}
