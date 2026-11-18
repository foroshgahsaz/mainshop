<?php

namespace Tests\Unit;

use App\Support\ShopVat;
use Tests\TestCase;

class ShopVatTest extends TestCase
{
    public function test_amount_on_taxable_rounds_ten_percent(): void
    {
        config(['shop.invoice.vat_percent' => 10]);

        $this->assertSame(100_000, ShopVat::amountOnTaxable(1_000_000));
        $this->assertSame(0, ShopVat::amountOnTaxable(0));
    }
}