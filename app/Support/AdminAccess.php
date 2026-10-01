<?php

namespace App\Support;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\UserResource;
use App\Models\User;

class AdminAccess
{
    /** @var array<class-string, true> */
    protected const SALES_MANAGER_RESOURCES = [
        OrderResource::class => true,
        PaymentResource::class => true,
        UserResource::class => true,
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
}
