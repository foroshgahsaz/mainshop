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
use App\Filament\Resources\UserResource;
use App\Models\User;

class AdminAccess
{
    private static ?User $memoUser = null;

    private static ?bool $memoIsFullAdmin = null;

    private static ?bool $memoIsSalesManagerOnly = null;

    /** @var array<class-string, true> */
    protected const SALES_MANAGER_RESOURCES = [
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
    ];

    /** @var list<string> */
    protected const SALES_MANAGER_ROUTE_PATTERNS = [
        'filament.admin.pages.dashboard',
        'filament.admin.resources.orders.*',
        'filament.admin.resources.payments.*',
        'filament.admin.resources.users.index',
        'filament.admin.resources.users.edit',
        'filament.admin.resources.products.*',
        'filament.admin.resources.categories.*',
        'filament.admin.resources.attributes.*',
        'filament.admin.resources.product-families.*',
        'filament.admin.resources.product-plants.*',
        'filament.admin.resources.product-templates.*',
        'filament.admin.resources.brands.*',
        'filament.admin.resources.media-files.*',
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

    public static function canAccessAdminResource(string $resourceClass): bool
    {
        if (static::isFullAdmin()) {
            return true;
        }

        if (! static::isSalesManagerOnly()) {
            return false;
        }

        return isset(static::SALES_MANAGER_RESOURCES[$resourceClass]);
    }

    public static function canManageShopInAdmin(?User $user = null): bool
    {
        return static::isFullAdmin($user);
    }

    public static function canManageProductsInAdmin(?User $user = null): bool
    {
        $user ??= static::user();

        return static::isFullAdmin($user) || static::isSalesManagerOnly($user);
    }

    /** @return list<string> */
    public static function salesManagerAllowedRoutePatterns(): array
    {
        return self::SALES_MANAGER_ROUTE_PATTERNS;
    }
}
