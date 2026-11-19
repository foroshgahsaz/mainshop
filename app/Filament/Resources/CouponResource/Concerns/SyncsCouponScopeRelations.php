<?php

namespace App\Filament\Resources\CouponResource\Concerns;

use App\Models\Coupon;

trait SyncsCouponScopeRelations
{
    /** @var list<int> */
    protected array $pendingProductIds = [];

    /** @var list<int> */
    protected array $pendingCategoryIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractScopeFields(array $data): array
    {
        $this->pendingProductIds = array_values(array_filter(
            array_map('intval', $data['product_ids'] ?? []),
            fn (int $id) => $id > 0,
        ));
        $this->pendingCategoryIds = array_values(array_filter(
            array_map('intval', $data['category_ids'] ?? []),
            fn (int $id) => $id > 0,
        ));

        unset($data['product_ids'], $data['category_ids']);

        if (! isset($data['scope_type']) || $data['scope_type'] === '') {
            $data['scope_type'] = Coupon::SCOPE_ALL;
        }

        return $data;
    }

    protected function syncCouponScopeRelations(Coupon $coupon): void
    {
        if ($coupon->scope_type === Coupon::SCOPE_PRODUCTS) {
            $coupon->products()->sync($this->pendingProductIds);
            $coupon->categories()->detach();

            return;
        }

        if ($coupon->scope_type === Coupon::SCOPE_CATEGORIES) {
            $coupon->categories()->sync($this->pendingCategoryIds);
            $coupon->products()->detach();

            return;
        }

        $coupon->products()->detach();
        $coupon->categories()->detach();
    }
}