<?php

namespace App\Providers;

use App\Support\ShopDate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class JalaliDateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Carbon::setLocale('fa');
        Date::use(Carbon::class);

        Carbon::macro('shopJalali', function (?string $format = null): string {
            /** @var CarbonInterface $this */
            return ShopDate::format($this, $format ?? 'Y/m/d H:i');
        });

        TextColumn::configureUsing(function (TextColumn $column): void {
            $name = $column->getName();

            if (! self::isJalaliDateAttribute($name)) {
                return;
            }

            if ($name === 'published_at') {
                $column->jalaliDate();
            } else {
                $column->jalaliDateTime();
            }
        }, isImportant: true);

        TextEntry::configureUsing(function (TextEntry $entry): void {
            $name = $entry->getName();

            if (! self::isJalaliDateAttribute($name)) {
                return;
            }

            if ($name === 'published_at') {
                $entry->jalaliDate();
            } else {
                $entry->jalaliDateTime();
            }
        }, isImportant: true);

        DateTimePicker::configureUsing(function (DateTimePicker $picker): void {
            $picker->jalali();
        }, isImportant: true);

        DatePicker::configureUsing(function (DatePicker $picker): void {
            $picker->jalali();
        }, isImportant: true);
    }

    protected static function isJalaliDateAttribute(string $name): bool
    {
        return str_ends_with($name, '_at')
            || in_array($name, ['published_at', 'stock_reserved_until'], true);
    }
}
