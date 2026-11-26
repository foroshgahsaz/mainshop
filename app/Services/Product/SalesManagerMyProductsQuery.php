<?php

namespace App\Services\Product;

use Illuminate\Database\Eloquent\Builder;

/**
 * لیست «محصولات من» برای مدیر فروش: ثبت‌شده با created_by،
 * یا قدیمی (created_by خالی) با آخرین ویرایش توسط همان مدیر.
 */
final class SalesManagerMyProductsQuery
{
    public static function apply(Builder $query, int $salesManagerUserId): Builder
    {
        return $query->where(function (Builder $inner) use ($salesManagerUserId): void {
            $inner
                ->where('created_by_user_id', $salesManagerUserId)
                ->orWhere(function (Builder $legacy) use ($salesManagerUserId): void {
                    $legacy
                        ->whereNull('created_by_user_id')
                        ->where('last_edited_by_user_id', $salesManagerUserId);
                });
        });
    }
}