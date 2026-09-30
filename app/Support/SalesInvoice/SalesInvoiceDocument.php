<?php

namespace App\Support\SalesInvoice;

final class SalesInvoiceDocument
{
    /**
     * @param  array<string, string|null>  $seller
     * @param  array<string, string|null>  $buyer
     * @param  list<array{row: int, sku: string, name: string, unit: string, quantity: int, unit_price: int, line_total: int}>  $lines
     */
    public function __construct(
        public string $title,
        public string $invoiceNumber,
        public string $invoiceDate,
        public array $seller,
        public array $buyer,
        public array $lines,
        public int $quantityTotal,
        public int $itemsTotal,
        public int $discountAmount,
        public int $shippingAmount,
        public int $finalAmount,
        public ?string $paymentMethodLabel = null,
        public ?string $freightLabel = null,
        public ?string $notes = null,
    ) {}

    public function payableAmount(): int
    {
        return max(0, $this->finalAmount);
    }
}
