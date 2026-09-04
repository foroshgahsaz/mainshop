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
            ->helperText('از کتابخانه انتخاب کنید یا فایل جدید آپلود کنید. تا پایان آپلود صبر کنید، بعد ذخیره کنید.')
            ->validationMessages([
                'required' => 'انتخاب تصویر الزامی است.',
                'uploaded' => 'فایل آپلود نشد. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
            ]);
    }
}
