<?php

namespace App\Support\SalesInvoice;

final class SalesInvoiceDocument
{
    /**
     * @param  array<string, string|null>  $seller
     * @param  array<string, string|null>  $buyer
     * @param  list<array{row: int, sku: string, name: string, unit: string, quantity: int, unit_price: int, line_discount?: int, line_total: int, is_adjustment?: bool}>  $lines
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
        public int $feesTotal = 0,
        public ?string $paymentMethodLabel = null,
        public ?string $freightLabel = null,
        public ?string $notes = null,
    ) {}

    public function showsLineDiscountColumn(): bool
    {
        foreach ($this->lines as $line) {
            if ((int) ($line['line_discount'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    public function payableAmount(): int
    {
        return max(0, $this->finalAmount);
    }
}
