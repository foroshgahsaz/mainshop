<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;

class CouponApplicabilityService
{
    public function appliesToAll(Coupon $coupon): bool
    {
        $type = $coupon->scope_type ?? Coupon::SCOPE_ALL;

        return $type === Coupon::SCOPE_ALL || $type === '';
    }

    /**
     * @param  iterable<int, array{product_id: int, quantity: int, price: int, line_total?: int}>  $lines
     */
    public function eligibleSubtotal(Coupon $coupon, iterable $lines): int
    {
        if ($this->appliesToAll($coupon)) {
            return $this->grossFromLines($lines);
        }

        $coupon->loadMissing(['products', 'categories']);
        $allowedProducts = $coupon->products->pluck('id')->flip();
        $allowedCategories = $coupon->categories->pluck('id')->flip();

        $linesCollection = collect($lines);
        $productIds = $linesCollection->pluck('product_id')->unique()->filter()->values();

        $categoryByProduct = $productIds->isEmpty()
            ? collect()
            : Product::query()->whereIn('id', $productIds)->pluck('category_id', 'id');

        $sum = 0;

        foreach ($lines as $line) {
            $productId = (int) $line['product_id'];
            $lineTotal = isset($line['line_total'])
                ? (int) $line['line_total']
                : (int) $line['price'] * (int) $line['quantity'];

            if ($coupon->scope_type === Coupon::SCOPE_PRODUCTS) {
                if ($allowedProducts->has($productId)) {
                    $sum += $lineTotal;
                }

                continue;
            }

            if ($coupon->scope_type === Coupon::SCOPE_CATEGORIES) {
                $categoryId = $categoryByProduct[$productId] ?? null;
                if ($categoryId !== null && $allowedCategories->has((int) $categoryId)) {
                    $sum += $lineTotal;
                }
            }
        }

        return $sum;
    }

    public function scopeFailureMessage(Coupon $coupon, int $eligibleSubtotal): ?string
    {
        if ($this->appliesToAll($coupon)) {
            return null;
        }

        if ($eligibleSubtotal > 0) {
            return null;
        }

        if ($coupon->scope_type === Coupon::SCOPE_PRODUCTS) {
            return 'هیچ‌یک از محصولات سبد در محدودهٔ این کد تخفیف نیست.';
        }

        return 'هیچ‌یک از اقلام سبد در دسته‌بندی‌های مشمول این کد تخفیف نیست.';
    }

    /** @param  Collection<int, array<string, mixed>>  $items */
    public function cartLinesFromEnrichedItems(Collection $items): array
    {
        return $items
            ->map(fn (array $item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'price' => (int) $item['price'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  iterable<int, array{product_id: int, quantity: int, price: int, line_total?: int}>  $lines
     */
    public function grossFromLines(iterable $lines): int
    {
        $sum = 0;

        foreach ($lines as $line) {
            $sum += isset($line['line_total'])
                ? (int) $line['line_total']
                : (int) $line['price'] * (int) $line['quantity'];
        }

        return $sum;
    }
}