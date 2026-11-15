<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * پیش‌نمایش فروشگاه برای مدیر سیستم، مدیر فروش، و کاربران با دسترسی «تست درگاه».
 */
final class ShopStaffPreview
{
    /** نوار ادمین بالای فروشگاه — فقط مدیر سیستم / مدیر فروش */
    public static function viewerIsStaff(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user instanceof User
            && $user->status
            && ($user->isAdmin() || $user->isSalesManager());
    }

    /** دیدن آیتم‌های admin_shop_preview در فروشگاه */
    public static function viewerCanSeePreview(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! $user->status) {
            return false;
        }

        return $user->isAdmin()
            || $user->isSalesManager()
            || $user->canShopGatewayTest();
    }

    public static function enabled(): bool
    {
        return self::viewerCanSeePreview();
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
