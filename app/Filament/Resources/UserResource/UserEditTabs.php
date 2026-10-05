<?php

namespace App\Filament\Resources\UserResource;

use App\Filament\Resources\UserResource\Pages\EditUser;
use Filament\Forms\Form;

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

    /**
     * Livewire save requests do not include ?tab=…; keep the tab from the EditUser page component.
     */
    public static function resolveActiveForForm(Form $form, string $operation): string
    {
        $livewire = $form->getLivewire();

        if ($livewire instanceof EditUser) {
            return $livewire->getActiveUserEditTab();
        }

        return self::resolveActive($operation);
    }

    public static function isReadOnly(string $tab): bool
    {
        return in_array($tab, [self::TAB_ORDERS, self::TAB_PAYMENTS], true);
    }
}
