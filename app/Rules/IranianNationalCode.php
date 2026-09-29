<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IranianNationalCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = $this->normalize((string) $value);

        if (! preg_match('/^\d{10}$/', $code)) {
            $fail('کد ملی باید ۱۰ رقم باشد.');

            return;
        }

        if (preg_match('/^(\d)\1{9}$/', $code)) {
            $fail('کد ملی نامعتبر است.');

            return;
        }

        $check = (int) $code[9];
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }

        $remainder = $sum % 11;
        $valid = ($remainder < 2 && $check === $remainder)
            || ($remainder >= 2 && $check === 11 - $remainder);

        if (! $valid) {
            $fail('کد ملی نامعتبر است.');
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
