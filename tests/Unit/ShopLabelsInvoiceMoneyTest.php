<?php

namespace Tests\Unit;

use App\Support\ShopLabels;
use Tests\TestCase;

class ShopLabelsInvoiceMoneyTest extends TestCase
{
    public function test_format_invoice_money_converts_toman_to_rial_label(): void
    {
        $this->assertSame('1,000,000 ریال', ShopLabels::formatInvoiceMoney(100_000));
    }

    public function test_invoice_amount_in_rial_multiplies_by_ten(): void
    {
        $this->assertSame(1_035_495_880, ShopLabels::invoiceAmountInRial(103_549_588));
    }
}
