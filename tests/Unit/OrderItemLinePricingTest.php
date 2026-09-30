<?php

namespace Tests\Unit;

use App\Models\OrderItem;
use App\Support\Order\OrderItemLinePricing;
use PHPUnit\Framework\TestCase;

class OrderItemLinePricingTest extends TestCase
{
    public function test_percent_discount_on_line(): void
    {
        $item = new OrderItem([
            'price' => 100_000,
            'quantity' => 2,
            'line_discount_type' => OrderItemLinePricing::DISCOUNT_PERCENT,
            'line_discount_value' => 10,
        ]);

        $this->assertSame(200_000, OrderItemLinePricing::grossAmount($item));
        $this->assertSame(20_000, OrderItemLinePricing::discountAmount($item));
        $this->assertSame(180_000, OrderItemLinePricing::netAmount($item));
    }

    public function test_fixed_discount_capped_at_gross(): void
    {
        $item = new OrderItem([
            'price' => 50_000,
            'quantity' => 1,
            'line_discount_type' => OrderItemLinePricing::DISCOUNT_FIXED,
            'line_discount_value' => 999_999,
        ]);

        $this->assertSame(50_000, OrderItemLinePricing::discountAmount($item));
        $this->assertSame(0, OrderItemLinePricing::netAmount($item));
    }
}
