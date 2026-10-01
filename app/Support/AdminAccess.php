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
    ];

    public static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function isFullAdmin(?User $user = null): bool
    {
        $user ??= static::user();

        return $user?->isAdmin() ?? false;
    }

    public static function isSalesManagerOnly(?User $user = null): bool
    {
        $user ??= static::user();

        return $user !== null && $user->isSalesManager() && ! $user->isAdmin();
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
