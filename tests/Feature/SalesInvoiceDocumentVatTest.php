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

    public function test_financial_breakdown_adds_ten_percent_vat_line_amount(): void
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
            finalAmount: 1_000_000,
            showVatBreakdown: true,
        );

        $this->assertSame(90909, $doc->vatAmount());
        $this->assertSame(909_091, $doc->payableAmountExcludingVat());
        $this->assertSame(909_091, $doc->priceExcludingVat(1_000_000));
        $this->assertSame(1_000_000, $doc->payableAmount());
    }

    public function test_product_lines_vat_breakdown_for_representative_style_invoice(): void
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
            finalAmount: 1_100_000,
            showVatBreakdown: true,
        );

        $this->assertSame(1_000_000, $doc->productLinesNetTotal());
        $this->assertSame(100_000, $doc->productLinesVatAmount());
        $this->assertSame(1_100_000, $doc->productLinesGrossTotal());
        $this->assertSame(500_000, $doc->lineAmountForDisplay(550_000));
    }

    public function test_customer_document_hides_vat(): void
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
