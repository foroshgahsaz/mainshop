<?php

namespace App\Filament\Resources\UserResource;

class UserEditTabs
{
    public const TAB_PROFILE = 'profile';

    public const TAB_ACCOUNT = 'account';

    public const TAB_ORDERS = 'orders';

    public const TAB_PAYMENTS = 'payments';

    public const TAB_ACCESS = 'access';

    /** @return array<string, array{label: string, icon: string}> */
    public static function definitions(string $operation): array
    {
        $tabs = [
            self::TAB_PROFILE => ['label' => 'پروفایل', 'icon' => 'fa-user-circle'],
            self::TAB_ACCOUNT => ['label' => 'اطلاعات حساب', 'icon' => 'fa-id-card'],
        ];

        if ($operation === 'edit') {
            $tabs[self::TAB_ORDERS] = ['label' => 'لیست سفارش‌ها', 'icon' => 'fa-shopping-bag'];
            $tabs[self::TAB_PAYMENTS] = ['label' => 'لیست پرداخت‌ها', 'icon' => 'fa-credit-card'];
        }

        $tabs[self::TAB_ACCESS] = ['label' => 'نوع کاربر و دسترسی', 'icon' => 'fa-shield-halved'];

        return $tabs;
    }

    public static function resolveActive(string $operation): string
    {
        $tab = request()->query('tab', self::TAB_PROFILE);
        $tab = is_string($tab) ? $tab : self::TAB_PROFILE;

        if (! array_key_exists($tab, self::definitions($operation))) {
            return self::TAB_PROFILE;
        }

        return $tab;
    }

    public static function isReadOnly(string $tab): bool
    {
        return in_array($tab, [self::TAB_ORDERS, self::TAB_PAYMENTS], true);
    }
}
