<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\TextColumn;

final class UserTableColumns
{
    /**
     * @param  'user.name'|'name'|string  $namePath
     */
    public static function nameWithPhone(string $namePath = 'user.name', string $label = 'کاربر'): TextColumn
    {
        $phonePath = $namePath === 'name' ? 'phone' : str_replace('.name', '.phone', $namePath);

        return TextColumn::make($namePath)
            ->label($label)
            ->description(fn ($record): ?string => self::phoneForRecord($record, $phonePath))
            ->placeholder('—')
            ->wrap()
            ->searchable();
    }

    /** نام کاربر بدون زیرنویس موبایل (مثلاً لیست سفارش‌ها). */
    public static function nameOnly(string $namePath = 'user.name', string $label = 'کاربر'): TextColumn
    {
        return TextColumn::make($namePath)
            ->label($label)
            ->placeholder('—')
            ->wrap()
            ->searchable();
    }

    public static function phone(string $phonePath = 'phone', string $label = 'موبایل'): TextColumn
    {
        return TextColumn::make($phonePath)
            ->label($label)
            ->placeholder('—')
            ->copyable()
            ->searchable()
            ->extraCellAttributes(['dir' => 'ltr']);
    }

    /**
     * @param  mixed  $record
     */
    private static function phoneForRecord($record, string $phonePath): ?string
    {
        $phone = data_get($record, $phonePath);

        return filled($phone) ? (string) $phone : null;
    }
}
