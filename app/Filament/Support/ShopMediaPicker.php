<?php

namespace App\Filament\Support;

use App\Filament\Forms\Components\MediaPicker;

class ShopMediaPicker
{
    public static function image(string $name, string $directory, string $label): MediaPicker
    {
        return MediaPicker::make($name)
            ->label($label)
            ->directory($directory)
            ->maxSize(51200)
            ->helperText('روی «انتخاب / تغییر تصویر» بزنید — از مرکز فایل انتخاب کنید یا فایل جدید بارگذاری کنید.')
            ->validationMessages([
                'required' => 'انتخاب تصویر الزامی است.',
            ]);
    }
}
