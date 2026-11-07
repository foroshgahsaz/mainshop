<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * پیش‌نمایش فروشگاه برای مدیر سیستم و مدیر فروش (بدون نمایش به مشتری).
 */
final class ShopStaffPreview
{
    public static function viewerIsStaff(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user instanceof User
            && $user->status
            && ($user->isAdmin() || $user->isSalesManager());
    }

    public static function enabled(): bool
    {
        return self::viewerIsStaff();
    }

    public static function modelVisibleOnShop(Model $model, ?bool $asStaff = null): bool
    {
        $asStaff ??= self::enabled();

        $preview = (bool) ($model->admin_shop_preview ?? false);
        $active = (bool) ($model->is_active ?? false);

        if ($preview) {
            return $asStaff;
        }

        return $active;
    }

    /** @param  Builder<Model>  $query */
    public static function scopeVisibleOnShop(Builder $query, ?bool $asStaff = null): Builder
    {
        $asStaff ??= self::enabled();

        if ($asStaff) {
            return $query->where(function (Builder $w): void {
                $w->where('admin_shop_preview', true)
                    ->orWhere(function (Builder $inner): void {
                        $inner->where('is_active', true)
                            ->where('admin_shop_preview', false);
                    });
            });
        }

        return $query->where('is_active', true)
            ->where('admin_shop_preview', false);
    }
}
