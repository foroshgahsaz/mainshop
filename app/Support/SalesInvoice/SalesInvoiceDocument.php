<?php

namespace App\Support\SalesInvoice;

use App\Support\ShopVat;

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
        public bool $showVatBreakdown = true,
        public int $paidCashSuccessTomans = 0,
        public int $paidCreditSuccessTomans = 0,
    ) {}

    public function showsSuccessfulPaymentsBlock(): bool
    {
        return $this->paidCashSuccessTomans > 0 || $this->paidCreditSuccessTomans > 0;
    }

    public function vatPercent(): int
    {
        return ShopVat::percent();
    }

    /** پایهٔ مشمول مالیات بر ارزش افزوده (پس از تخفیف‌ها، بدون ارسال و هزینه‌های جانبی). */
    public function taxableGoodsAmount(): int
    {
        return max(0, $this->itemsTotal - $this->discountAmount);
    }

    public function vatAmount(): int
    {
        if (! $this->showVatBreakdown) {
            return 0;
        }

        return ShopVat::amountOnTaxable($this->taxableGoodsAmount());
    }

    /** @param  array<string, mixed>  $line */
    public function isProductLine(array $line): bool
    {
        return ! (bool) ($line['is_adjustment'] ?? false);
    }

    /** جمع مبلغ ردیف کالا. */
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

  /** جمع اقلام کالا برای نمایش (همان مبالغ ذخیره‌شده). */
    public function productLinesNetTotal(): int
    {
        return $this->productLinesGrossTotal();
    }

    /** سهم ارزش افزوده روی پایهٔ مشمول سفارش. */
    public function productLinesVatAmount(): int
    {
        return $this->vatAmount();
    }

    /** جمع کالا به‌همراه ردیف ارزش افزوده (قبل از ارسال و هزینه‌ها). */
    public function goodsTotalIncludingVat(): int
    {
        return $this->taxableGoodsAmount() + $this->vatAmount();
    }

    /** بهای واحد/ردیف برای نمایش. */
    public function lineAmountForDisplay(int $amountTomans, bool $isAdjustmentLine = false): int
    {
        return $amountTomans;
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