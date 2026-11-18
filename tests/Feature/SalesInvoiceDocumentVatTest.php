<?php

namespace Tests\Feature;

use App\Support\SalesInvoice\SalesInvoiceDocument;
use Tests\TestCase;

class SalesInvoiceDocumentVatTest extends TestCase
{
    public function test_shows_successful_payments_block_when_any_paid(): void
    {
        $doc = new SalesInvoiceDocument(
            title: 'پیش‌فاکتور',
            invoiceNumber: '1',
            invoiceDate: '1404/01/01',
            seller: [],
            buyer: [],
            lines: [],
            quantityTotal: 0,
            itemsTotal: 0,
            discountAmount: 0,
            shippingAmount: 0,
            finalAmount: 1_000_000,
            paidCashSuccessTomans: 500_000,
        );

        $this->assertTrue($doc->showsSuccessfulPaymentsBlock());

        $empty = new SalesInvoiceDocument(
            title: 'پیش‌فاکتور',
            invoiceNumber: '2',
            invoiceDate: '1404/01/01',
            seller: [],
            buyer: [],
            lines: [],
            quantityTotal: 0,
            itemsTotal: 0,
            discountAmount: 0,
            shippingAmount: 0,
            finalAmount: 1_000_000,
        );

        $this->assertFalse($empty->showsSuccessfulPaymentsBlock());
    }

    public function test_additive_ten_percent_vat_on_taxable_base(): void
    {
        config(['shop.invoice.vat_percent' => 10]);

        $doc = new SalesInvoiceDocument(
            title: 'فاکتور',
            invoiceNumber: '1',
            invoiceDate: '1404/01/01',
            seller: [],
            buyer: [],
            lines: [],
            quantityTotal: 0,
            itemsTotal: 1_000_000,
            discountAmount: 0,
            shippingAmount: 0,
            finalAmount: 1_100_000,
            showVatBreakdown: true,
        );

        $this->assertSame(100_000, $doc->vatAmount());
        $this->assertSame(1_100_000, $doc->goodsTotalIncludingVat());
        $this->assertSame(1_100_000, $doc->payableAmount());
    }

    public function test_product_lines_vat_matches_order_taxable(): void
    {
        config(['shop.invoice.vat_percent' => 10]);

        $doc = new SalesInvoiceDocument(
            title: 'پیش‌فاکتور',
            invoiceNumber: '1',
            invoiceDate: '1404/01/01',
            seller: [],
            buyer: [],
            lines: [
                [
                    'row' => 1,
                    'sku' => 'A1',
                    'name' => 'کالا',
                    'unit' => 'عدد',
                    'quantity' => 2,
                    'unit_price' => 550_000,
                    'line_total' => 1_100_000,
                ],
            ],
            quantityTotal: 2,
            itemsTotal: 1_100_000,
            discountAmount: 0,
            shippingAmount: 0,
            finalAmount: 1_210_000,
            showVatBreakdown: true,
        );

        $this->assertSame(1_100_000, $doc->productLinesNetTotal());
        $this->assertSame(110_000, $doc->productLinesVatAmount());
        $this->assertSame(550_000, $doc->lineAmountForDisplay(550_000));
    }

    public function test_vat_can_be_hidden_when_breakdown_disabled(): void
    {
        $doc = new SalesInvoiceDocument(
            title: 'فاکتور',
            invoiceNumber: '1',
            invoiceDate: '1404/01/01',
            seller: [],
            buyer: [],
            lines: [],
            quantityTotal: 0,
            itemsTotal: 1_000_000,
            discountAmount: 0,
            shippingAmount: 0,
            finalAmount: 1_000_000,
            showVatBreakdown: false,
        );

        $this->assertSame(0, $doc->vatAmount());
    }
}