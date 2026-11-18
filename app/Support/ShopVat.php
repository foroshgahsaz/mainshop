<?php

namespace App\Support;

final class ShopVat
{
    public static function percent(): int
    {
        return max(0, (int) config('shop.invoice.vat_percent', 10));
    }

  /** مبلغ ارزش افزوده روی پایهٔ مشمول (تومان)، گردشده. */
    public static function amountOnTaxable(int $taxableTomans): int
    {
        $percent = self::percent();
        if ($percent <= 0 || $taxableTomans <= 0) {
            return 0;
        }

        return (int) round($taxableTomans * $percent / 100);
    }

    public static function reminderText(): ?string
    {
        $percent = self::percent();
        if ($percent <= 0) {
            return null;
        }

        return "{$percent}٪ ارزش افزوده در پرداخت نهایی به مبلغ کالا اضافه می‌شود.";
    }
}