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

    /** ضریب قیمت با مالیات (مثلاً ۱.۱ برای ۱۰٪). */
    public function grossPriceMultiplier(): float
    {
        $percent = $this->vatPercent();

        return 1 + ($percent / 100);
    }

    /**
     * قیمت اصلی بدون مالیات = قیمت با مالیات ÷ (۱ + درصد/۱۰۰).
     * مبالغ فروشگاه به‌صورت شامل ارزش افزوده ذخیره می‌شوند.
     */
    public function priceExcludingVat(int $amountWithVat): int
    {
        if ($this->vatPercent() <= 0) {
            return $amountWithVat;
        }

        return (int) round($amountWithVat / $this->grossPriceMultiplier());
    }

    /** مبلغ قابل پرداخت بدون سهم ارزش افزوده (فقط نمایش/PDF مالی). */
    public function payableAmountExcludingVat(): int
    {
        if (! $this->showVatBreakdown) {
            return $this->payableAmount();
        }

        return $this->priceExcludingVat($this->payableAmount());
    }

    /** سهم ارزش افزوده داخل مبلغ نهایی (قیمت با مالیات − قیمت بدون مالیات). */
    public function vatAmount(): int
    {
        if (! $this->showVatBreakdown) {
            return 0;
        }

        if ($this->vatPercent() <= 0) {
            return 0;
        }

        $gross = $this->payableAmount();

        return max(0, $gross - $this->priceExcludingVat($gross));
    }

    /** @param  array<string, mixed>  $line */
    public function isProductLine(array $line): bool
    {
        return ! (bool) ($line['is_adjustment'] ?? false);
    }

    /** جمع مبلغ ردیف کالا (شامل مالیات). */
    public function productLinesGrossTotal(): int
    {
        $sum = 0;
        foreach ($this->lines as $line) {
            if (! $this->isProductLine($line)) {
                continue;
            }
            $sum += (int) $line['line_total'];
        }

        return $sum;
    }

    /** جمع اقلام کالا بدون مالیات. */
    public function productLinesNetTotal(): int
    {
        if (! $this->showVatBreakdown) {
            return $this->productLinesGrossTotal();
        }

        return $this->priceExcludingVat($this->productLinesGrossTotal());
    }

    /** سهم ارزش افزوده داخل جمع کالاها. */
    public function productLinesVatAmount(): int
    {
        if (! $this->showVatBreakdown) {
            return 0;
        }

        $gross = $this->productLinesGrossTotal();

        return max(0, $gross - $this->priceExcludingVat($gross));
    }

    /** بهای واحد/ردیف برای PDF مالی (فقط ردیف کالا). */
    public function lineAmountForDisplay(int $amountWithVat, bool $isAdjustmentLine = false): int
    {
        if (! $this->showVatBreakdown || $isAdjustmentLine) {
            return $amountWithVat;
        }

        return $this->priceExcludingVat($amountWithVat);
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
