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
        $this->assertTrue(UserDisplayName::isPlaceholder('کاربر  9341'));
        $this->assertTrue(UserDisplayName::isPlaceholder('کاربر9341'));
        $this->assertFalse(UserDisplayName::isPlaceholder('علی رضایی'));
    }

    public function test_shop_profile_complete_requires_real_name_and_national_code(): void
    {
        $complete = (object) ['name' => 'علی رضایی', 'national_code' => '0067749828'];
        $this->assertTrue(UserDisplayName::isShopProfileComplete($complete));

        $placeholder = (object) ['name' => 'کاربر 1090', 'national_code' => '0067749828'];
        $this->assertFalse(UserDisplayName::isShopProfileComplete($placeholder));

        $missingCode = (object) ['name' => 'علی رضایی', 'national_code' => null];
        $this->assertFalse(UserDisplayName::isShopProfileComplete($missingCode));
    }
}
