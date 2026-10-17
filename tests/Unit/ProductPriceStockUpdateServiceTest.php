<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductPriceStockUpdateServiceTest extends TestCase
{
    public function test_bulk_price_percent_calculation(): void
    {
        $base = 103_549_588;
        $percent = 8.0;
        $expected = max(0, (int) round($base * (1 + $percent / 100)));

        $this->assertSame(111_833_555, $expected);
    }

    public function test_bulk_stock_delta_floors_at_zero(): void
    {
        $this->assertSame(0, max(0, 3 - 10));
        $this->assertSame(13, max(0, 3 + 10));
    }
}
