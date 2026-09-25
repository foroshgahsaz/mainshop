<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ShopFormatter;
use Tests\TestCase;

class ShopFormatterComparePriceTest extends TestCase
{
    public function test_it_returns_original_price_when_product_has_discount(): void
    {
        $product = new Product([
            'price' => 100000,
            'sale_price' => 80000,
        ]);

        $this->assertSame(100000, ShopFormatter::comparePrice($product));
    }

    public function test_it_returns_null_when_product_has_no_discount(): void
    {
        $product = new Product([
            'price' => 100000,
            'sale_price' => null,
        ]);

        $this->assertNull(ShopFormatter::comparePrice($product));
    }

    public function test_it_returns_original_price_when_variant_has_discount(): void
    {
        $variant = new ProductVariant([
            'price' => 50000,
            'sale_price' => 40000,
        ]);

        $this->assertSame(50000, ShopFormatter::comparePrice($variant));
    }
}
