<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DarikMemberCode implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($code === '') {
            return;
        }

        if (! preg_match('/^5892\d{12}$/', $code)) {
            $fail('شناسه کیف پول داریک باید ۱۶ رقم و با 5892 شروع شود.');
        }
    }

    public static function normalize(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }
}
