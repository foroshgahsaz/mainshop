<?php

namespace App\Support;

use App\Filament\Resources\AttributeResource;
use App\Filament\Resources\BrandResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\MediaFileResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\ProductFamilyResource;
use App\Filament\Resources\ProductPlantResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductTemplateResource;
use App\Filament\Resources\ReservationRequestResource;
use App\Filament\Resources\UserResource;
use App\Models\User;

class AdminAccess
{
    private static ?User $memoUser = null;

    private static ?bool $memoIsFullAdmin = null;

    private static ?bool $memoIsSalesManagerOnly = null;

    private static ?bool $memoIsBankdarOnly = null;

    private static ?bool $memoIsRepresentativeProfileOnly = null;

    /** @var array<class-string, true> */
    protected const LIMITED_STAFF_RESOURCES = [
        OrderResource::class => true,
        PaymentResource::class => true,
        UserResource::class => true,
        ProductResource::class => true,
        CategoryResource::class => true,
        AttributeResource::class => true,
        ProductFamilyResource::class => true,
        ProductPlantResource::class => true,
        ProductTemplateResource::class => true,
        BrandResource::class => true,
        MediaFileResource::class => true,
        ReservationRequestResource::class => true,
    ];

    /** @var list<string> */
    protected const LIMITED_STAFF_ROUTE_PATTERNS = [
        'filament.admin.pages.dashboard',
        'filament.admin.resources.orders.*',
        'filament.admin.resources.payments.*',
        'filament.admin.resources.users.index',
        'filament.admin.resources.users.create',
        'filament.admin.resources.users.edit',
        'filament.admin.resources.products.*',
        'filament.admin.pages.product-price-stock-update',
        'filament.admin.pages.product-list-import',
        'filament.admin.product-import.template',
        'filament.admin.resources.categories.*',
        'filament.admin.resources.attributes.*',
        'filament.admin.resources.product-families.*',
        'filament.admin.resources.product-plants.*',
        'filament.admin.resources.product-templates.*',
        'filament.admin.resources.brands.*',
        'filament.admin.resources.media-files.*',
        'filament.admin.resources.reservation-requests.*',
        'livewire.upload-file',
        'livewire.preview-file',
        'livewire.update',
        'default.livewire.update',
    ];

    public static function user(): ?User
    {
        if (static::$memoUser !== null) {
            return static::$memoUser;
        }

        $user = auth()->user();

        static::$memoUser = $user instanceof User ? $user : null;

        return static::$memoUser;
    }

    public static function isFullAdmin(?User $user = null): bool
    {
        if ($user !== null) {
            return $user->isAdmin();
        }

        if (static::$memoIsFullAdmin !== null) {
            return static::$memoIsFullAdmin;
        }

        static::$memoIsFullAdmin = static::user()?->isAdmin() ?? false;

        return static::$memoIsFullAdmin;
    }

    public static function isSalesManagerOnly(?User $user = null): bool
    {
        if ($user !== null) {
            return $user->isSalesManager() && ! $user->isAdmin();
        }

        if (static::$memoIsSalesManagerOnly !== null) {
            return static::$memoIsSalesManagerOnly;
        }

        $current = static::user();
        static::$memoIsSalesManagerOnly = $current !== null
            && $current->isSalesManager()
            && ! $current->isAdmin();

        return static::$memoIsSalesManagerOnly;
    }

    public static function isBankdarOnly(?User $user = null): bool
    {
        if ($user !== null) {
            return $user->isBankdar() && ! $user->isAdmin() && ! $user->isSalesManager();
        }

        if (static::$memoIsBankdarOnly !== null) {
            return static::$memoIsBankdarOnly;
        }

        $current = static::user();
        static::$memoIsBankdarOnly = $current !== null
            && $current->isBankdar()
            && ! $current->isAdmin()
            && ! $current->isSalesManager();

        return static::$memoIsBankdarOnly;
    }

    /**
     * نمایندهٔ خالص — فقط مجاز به ویرایش پروفایل خودش در ادمین (نه منوی فروش).
     */
    public static function isRepresentativeProfileOnly(?User $user = null): bool
    {
        if ($user !== null) {
            return $user->isRepresentative()
                && ! $user->isAdmin()
                && ! $user->isSalesManager()
                && ! $user->isBankdar();
        }

        if (static::$memoIsRepresentativeProfileOnly !== null) {
            return static::$memoIsRepresentativeProfileOnly;
        }

        $current = static::user();
        static::$memoIsRepresentativeProfileOnly = $current !== null
            && $current->isRepresentative()
            && ! $current->isAdmin()
            && ! $current->isSalesManager()
            && ! $current->isBankdar();

        return static::$memoIsRepresentativeProfileOnly;
    }

    /** مدیر فروش یا بنکدار با منوی محدود ادمین (نه مدیر سیستم). */
    public static function isLimitedStaffOnly(?User $user = null): bool
    {
        return static::isSalesManagerOnly($user) || static::isBankdarOnly($user);
    }

    public static function canAccessAdminResource(string $resourceClass): bool
    {
        if (static::isFullAdmin()) {
            return true;
        }

        if (! static::isLimitedStaffOnly()) {
            return false;
        }

        return isset(static::LIMITED_STAFF_RESOURCES[$resourceClass]);
    }

    public static function canManageShopInAdmin(?User $user = null): bool
    {
        return static::isFullAdmin($user);
    }

    /** بنکدار (و ادمین) می‌تواند نماینده بسازد. */
    public static function canCreateRepresentativesInAdmin(?User $user = null): bool
    {
        return static::isFullAdmin($user) || static::isBankdarOnly($user);
    }

    /** مدیر فروش (و ادمین) می‌تواند بنکدار بسازد. */
    public static function canCreateBankdarsInAdmin(?User $user = null): bool
    {
        return static::isFullAdmin($user) || static::isSalesManagerOnly($user);
    }

    /**
     * مدیریت محصولات در ادمین: لیست، افزودن، ویرایش، بروزرسانی قیمت، ورود اکسل.
     * شامل مدیر سیستم، مدیر فروش و بنکدار.
     */
    public static function canManageProductsInAdmin(?User $user = null): bool
    {
        $user ??= static::user();

        return static::isFullAdmin($user) || static::isLimitedStaffOnly($user);
    }

    /** ورود لیست محصول از اکسل (همان سطح دسترسی مدیریت محصول). */
    public static function canImportProductsInAdmin(?User $user = null): bool
    {
        return static::canManageProductsInAdmin($user);
    }

    /** @return list<string> */
    public static function salesManagerAllowedRoutePatterns(): array
    {
        return self::LIMITED_STAFF_ROUTE_PATTERNS;
    }

    /** @return list<string> */
    public static function limitedStaffAllowedRoutePatterns(): array
    {
        return self::LIMITED_STAFF_ROUTE_PATTERNS;
    }
}
