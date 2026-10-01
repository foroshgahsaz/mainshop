<?php

namespace App\Support;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PaymentResource;
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
}
