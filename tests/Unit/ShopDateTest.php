<?php

namespace Tests\Unit;

use App\Support\ShopDate;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ShopDateTest extends TestCase
{
    public function test_formats_gregorian_instant_as_jalali_string(): void
    {
        $carbon = Carbon::parse('2025-03-21 09:00:00', 'UTC');

        $formatted = ShopDate::format($carbon, 'Y/m/d H:i');

        $this->assertSame('1404/01/01 12:30', $formatted);
    }

    public function test_helper_returns_dash_for_null(): void
    {
        $this->assertSame('—', shop_jalali(null));
    }
}
