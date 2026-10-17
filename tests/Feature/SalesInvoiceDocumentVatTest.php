<?php

namespace Tests\Feature;

use App\Support\SalesInvoice\SalesInvoiceDocument;
use Tests\TestCase;

class SalesInvoiceDocumentVatTest extends TestCase
{
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

        $this->assertSame(100_000, $doc->vatAmount());
        $this->assertSame(1_000_000, $doc->payableAmount());
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
