<?php

namespace App\Models\Concerns;

use App\Support\ShopStaffPreview;
use Illuminate\Database\Eloquent\Builder;

trait HasAdminShopPreview
{
    public function isVisibleOnShop(?bool $asStaff = null): bool
    {
        return ShopStaffPreview::modelVisibleOnShop($this, $asStaff);
    }

    /** @param  Builder<static>  $query */
    public function scopeVisibleOnShop(Builder $query, ?bool $asStaff = null): Builder
    {
        return ShopStaffPreview::scopeVisibleOnShop($query, $asStaff);
    }
}
