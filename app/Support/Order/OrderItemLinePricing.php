<?php

namespace App\Support\Order;

use App\Models\OrderItem;

final class OrderItemLinePricing
{
    public const DISCOUNT_NONE = 'none';

    public const DISCOUNT_FIXED = 'fixed';

    public const DISCOUNT_PERCENT = 'percent';

    /** @return list<string> */
    public static function discountTypes(): array
    {
        return [self::DISCOUNT_NONE, self::DISCOUNT_FIXED, self::DISCOUNT_PERCENT];
    }

    public static function grossAmount(OrderItem $item): int
    {
        return (int) $item->price * (int) $item->quantity;
    }

    public static function discountAmount(OrderItem $item): int
    {
        $gross = self::grossAmount($item);
        $type = (string) ($item->line_discount_type ?? self::DISCOUNT_NONE);
        $value = (int) ($item->line_discount_value ?? 0);

        if ($gross <= 0 || $value <= 0 || $type === self::DISCOUNT_NONE) {
            return 0;
        }

        if ($type === self::DISCOUNT_FIXED) {
            return min($gross, $value);
        }

        if ($type === self::DISCOUNT_PERCENT) {
            $percent = min(100, max(0, $value));

            return min($gross, (int) floor($gross * $percent / 100));
        }

        return 0;
    }

    public static function netAmount(OrderItem $item): int
    {
        return max(0, self::grossAmount($item) - self::discountAmount($item));
    }

    public static function assertValidDiscount(string $type, int $value): void
    {
        if (! in_array($type, self::discountTypes(), true)) {
            throw new \InvalidArgumentException('نوع تخفیف ردیف معتبر نیست.');
        }

        if ($type === self::DISCOUNT_NONE) {
            return;
        }

        if ($type === self::DISCOUNT_PERCENT && ($value < 1 || $value > 100)) {
            throw new \InvalidArgumentException('درصد تخفیف باید بین ۱ تا ۱۰۰ باشد.');
        }

        if ($type === self::DISCOUNT_FIXED && $value < 1) {
            throw new \InvalidArgumentException('مبلغ تخفیف باید بزرگ‌تر از صفر باشد.');
        }
    }
}
