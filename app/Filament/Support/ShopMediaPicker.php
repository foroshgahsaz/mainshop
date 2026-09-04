<?php

namespace App\Filament\Support;

use App\Filament\Forms\Components\MediaPicker;

class ShopMediaPicker
{
    public static function image(string $name, string $directory, string $label): MediaPicker
    {
        return MediaPicker::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->maxSize(51200)
            ->imagePreviewHeight('150')
            ->helperText('روی «مرکز فایل» بزنید: از تصاویر موجود انتخاب کنید یا فایل جدید بارگذاری کنید، بعد تأیید و ذخیره کنید.')
            ->validationMessages([
                'required' => 'انتخاب تصویر الزامی است.',
                'uploaded' => 'فایل آپلود نشد. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
            ]);
    }
}
