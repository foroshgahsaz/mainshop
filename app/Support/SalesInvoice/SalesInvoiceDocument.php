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
        public bool $showVatBreakdown = false,
    ) {}

    public function vatPercent(): int
    {
        return max(0, (int) config('shop.invoice.vat_percent', 10));
    }

    /** سهم ارزش افزوده از مبلغ نهایی (قیمت مشتری شامل مالیات است؛ این فقط برای PDF مالی). */
    public function vatAmount(): int
    {
        if (! $this->showVatBreakdown) {
            return 0;
        }

        $percent = $this->vatPercent();
        if ($percent <= 0) {
            return 0;
        }

        return (int) round($this->payableAmount() * $percent / 100);
    }

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
