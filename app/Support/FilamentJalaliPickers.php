<?php

namespace App\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;

final class FilamentJalaliPickers
{
    public static function registerDefaults(): void
    {
        if (! DateTimePicker::hasMacro('jalali')) {
            return;
        }

        $jalaliDate = static function (DatePicker|DateTimePicker|TimePicker $picker): void {
            $picker->jalali(weekdaysShort: true);

            if (method_exists($picker, 'hasToday')) {
                $picker->hasToday();
            }

            if (method_exists($picker, 'timezone')) {
                $picker->timezone(ShopDate::TIMEZONE);
            }
        };

        DatePicker::configureUsing(function (DatePicker $picker) use ($jalaliDate): void {
            $jalaliDate($picker);
        });

        DateTimePicker::configureUsing(function (DateTimePicker $picker) use ($jalaliDate): void {
            $jalaliDate($picker);
            $picker->seconds(true);
        });

        if (TimePicker::hasMacro('jalali')) {
            TimePicker::configureUsing(function (TimePicker $picker) use ($jalaliDate): void {
                $jalaliDate($picker);
            });
        }
    }
}
