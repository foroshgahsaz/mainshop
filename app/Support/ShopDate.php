<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

final class ShopDate
{
    public const TIMEZONE = 'Asia/Tehran';

    public static function format(mixed $value, string $format = 'Y/m/d H:i', string $empty = '—'): string
    {
        $carbon = self::toCarbon($value);

        if ($carbon === null) {
            return $empty;
        }

        return Jalalian::fromCarbon($carbon->timezone(self::TIMEZONE))->format($format);
    }

    public static function date(mixed $value, string $empty = '—'): string
    {
        return self::format($value, 'Y/m/d', $empty);
    }

    public static function dateTime(mixed $value, string $empty = '—'): string
    {
        return self::format($value, 'Y/m/d H:i', $empty);
    }

    public static function toCarbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
