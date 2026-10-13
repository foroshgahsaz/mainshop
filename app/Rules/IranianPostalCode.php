<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IranianPostalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = self::normalize((string) $value);

        if (! preg_match('/^\d{10}$/', $code)) {
            $fail('کد پستی باید ۱۰ رقم باشد.');

            return;
        }

        if (preg_match('/^(\d)\1{9}$/', $code)) {
            $fail('کد پستی نامعتبر است.');
        }
    }

    public static function normalize(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $value = str_replace($persian, range(0, 9), $value);
        $value = str_replace($arabic, range(0, 9), $value);

        return preg_replace('/\D/', '', $value) ?? '';
    }
}
