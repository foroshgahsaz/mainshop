<?php

namespace Tests\Unit;

use App\Support\UserDisplayName;
use PHPUnit\Framework\TestCase;

class UserDisplayNameTest extends TestCase
{
    public function test_detects_ascii_and_persian_digit_placeholders(): void
    {
        $this->assertTrue(UserDisplayName::isPlaceholder('کاربر 1090'));
        $this->assertTrue(UserDisplayName::isPlaceholder('کاربر ۱۰۹۰'));
        $this->assertFalse(UserDisplayName::isPlaceholder('علی رضایی'));
    }
}
