<?php

namespace App\Support;

use App\Rules\IranianNationalCode;

final class UserDisplayName
{
    public static function isShopProfileComplete(object $user): bool
    {
        if (self::isPlaceholder((string) ($user->name ?? ''))) {
            return false;
        }

        $code = IranianNationalCode::normalize((string) ($user->national_code ?? ''));

        return $code !== '';
    }
    public static function isPlaceholder(?string $name): bool
    {
        $name = trim((string) $name);

        if ($name === '') {
            return true;
        }

        $name = self::normalizeDigits($name);
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        if (! preg_match('/^کاربر\s*(.+)$/u', $name, $matches)) {
            return false;
        }

        $suffix = preg_replace('/\s+/u', '', $matches[1]) ?? $matches[1];

        return (bool) preg_match('/^\d{4}$/', $suffix);
    }

    public static function normalizeDigits(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $value = str_replace($persian, range(0, 9), $value);
        $value = str_replace($arabic, range(0, 9), $value);

        return $value;
    }
}
