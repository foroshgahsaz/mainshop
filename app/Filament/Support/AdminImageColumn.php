<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\ImageColumn;

class AdminImageColumn
{
    public static function make(string $name, int $height = 50, ?string $label = null): ImageColumn
    {
        return ImageColumn::make($name)
            ->label($label ?? 'تصویر')
            ->disk('public')
            ->visibility('public')
            ->height($height)
            ->defaultImageUrl(asset('shop/images/products/clothing.svg'))
            ->extraImgAttributes(['class' => 'rounded-lg object-cover']);
    }
}
