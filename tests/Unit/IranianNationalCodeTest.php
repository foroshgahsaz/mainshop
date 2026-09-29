<?php

namespace Tests\Unit;

use App\Rules\IranianNationalCode;
use PHPUnit\Framework\TestCase;

class IranianNationalCodeTest extends TestCase
{
    public function test_valid_national_code_passes(): void
    {
        $rule = new IranianNationalCode;
        $failed = false;

        $rule->validate('national_code', '0013542419', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_invalid_check_digit_fails(): void
    {
        $rule = new IranianNationalCode;
        $message = null;

        $rule->validate('national_code', '0013542410', function (string $msg) use (&$message): void {
            $message = $msg;
        });

        $this->assertNotNull($message);
    }

    public function test_normalize_persian_digits(): void
    {
        $this->assertSame('0013542419', IranianNationalCode::normalize('۰۰۱۳۵۴۲۴۱۹'));
    }
}
