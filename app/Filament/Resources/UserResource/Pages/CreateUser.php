<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\UserResource;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
use Filament\Forms\Form;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string|Htmlable
    {
        if (AdminAccess::isSalesManagerOnly()) {
            return 'افزودن بنکدار';
        }

        if (AdminAccess::isBankdarOnly()) {
            $kind = (string) ($this->form->getRawState()['bankdar_create_kind'] ?? 'representative');

            return $kind === 'customer' ? 'افزودن مشتری' : 'افزودن نماینده';
        }

        return parent::getTitle();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = static::applyAccessFieldsFromForm($this->form, $data);

        if (AdminAccess::isSalesManagerOnly()) {
            $provinceId = (int) ($data['bankdar_province_id'] ?? 0);
            app(SalesRegionService::class)->assertBankdarProvinceAvailable($provinceId ?: null);

            $data['is_bankdar'] = true;
            $data['is_representative'] = false;
            $data['is_admin'] = false;
            $data['is_author'] = false;
            $data['is_sales_manager'] = false;
            $data['created_by_sales_manager_id'] = (int) auth()->id();
            $data['bankdar_province_id'] = $provinceId;
            $data['status'] = $data['status'] ?? true;
        }

        if (AdminAccess::isBankdarOnly()) {
            $raw = $this->form->getRawState();
            $kind = (string) ($raw['bankdar_create_kind'] ?? 'representative');

            $data['is_admin'] = false;
            $data['is_author'] = false;
            $data['is_sales_manager'] = false;
            $data['is_bankdar'] = false;
            $data['status'] = $data['status'] ?? true;

            if ($kind === 'customer') {
                $repId = (int) ($data['created_by_representative_id'] ?? $raw['created_by_representative_id'] ?? 0);
                $actor = auth()->user();
                if ($repId <= 0 || ! $actor || ! app(SalesRegionService::class)->managesRepresentative(
                    $actor,
                    \App\Models\User::query()->findOrFail($repId),
                )) {
                    throw ValidationException::withMessages([
                        'data.created_by_representative_id' => 'نماینده باید زیرمجموعه شما باشد.',
                    ]);
                }

                $data['is_representative'] = false;
                $data['created_by_representative_id'] = $repId;
            } else {
                $data['is_representative'] = true;
                unset($data['created_by_representative_id']);
            }
        }

        return static::stripVirtualAccessFormKeys($data);
    }

    protected function afterCreate(): void
    {
        if (! $this->record->is_representative) {
            return;
        }

        $raw = $this->form->getRawState();

        if (AdminAccess::isBankdarOnly()) {
            if (($raw['bankdar_create_kind'] ?? 'representative') === 'customer') {
                return;
            }

            $bankdar = auth()->user();
            $provinceId = (int) ($bankdar?->bankdar_province_id ?? 0);
            if ($provinceId <= 0) {
                throw ValidationException::withMessages([
                    'data.rep_city_id' => 'استان بنکدار تعیین نشده است.',
                ]);
            }

            $this->record->representativeProfile()->create([
                'sales_manager_id' => (int) auth()->id(),
                'province_id' => $provinceId,
                'city_id' => $raw['rep_city_id'] ?? null,
                'max_active_reservations' => (int) ($raw['rep_max_active_reservations'] ?? 3),
            ]);

            return;
        }

        $managerId = (int) ($raw['rep_sales_manager_id'] ?? 0);

        $this->record->representativeProfile()->create([
            'sales_manager_id' => $managerId ?: null,
            'province_id' => $raw['rep_province_id'] ?? null,
            'city_id' => $raw['rep_city_id'] ?? null,
            'max_active_reservations' => (int) ($raw['rep_max_active_reservations'] ?? 3),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public static function applyAccessFieldsFromForm(Form $form, array $data): array
    {
        $raw = $form->getRawState();

        $userKind = $raw['user_kind'] ?? $data['user_kind'] ?? null;
        if ($userKind === null || $userKind === '') {
            $userKind = static::resolveUserKindFromFlags($data);
        }
        $data['user_kind'] = $userKind;

        if ($userKind === 'staff') {
            $role = (string) ($raw['staff_role'] ?? $data['staff_role'] ?? '');
            if ($role === '') {
                $role = static::resolveStaffRoleFromFlags($data) ?? '';
            }
            $data = static::applyStaffRoleFlags($data, $role !== '' ? $role : null);

            if ($role === 'bankdar') {
                $managerId = (int) ($raw['created_by_sales_manager_id'] ?? $data['created_by_sales_manager_id'] ?? 0);
                $provinceId = (int) ($raw['bankdar_province_id'] ?? $data['bankdar_province_id'] ?? 0);
                $data['created_by_sales_manager_id'] = $managerId > 0 ? $managerId : null;
                $data['bankdar_province_id'] = $provinceId > 0 ? $provinceId : null;
            } else {
                $data['created_by_sales_manager_id'] = null;
                $data['bankdar_province_id'] = null;
            }
        }

        return static::normalizeUserKind($data);
    }

    /** @param  array<string, mixed>  $data */
    public static function resolveUserKindFromFlags(array $data): string
    {
        foreach (['is_admin', 'is_author', 'is_representative', 'is_sales_manager', 'is_bankdar'] as $flag) {
            if (filter_var($data[$flag] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return 'staff';
            }
        }

        return 'customer';
    }

    /**
     * اولویت نقش تکی: مدیر سیستم → مدیر فروش → بنکدار → نماینده → نویسنده.
     *
     * @param  array<string, mixed>  $data
     */
    public static function resolveStaffRoleFromFlags(array $data): ?string
    {
        $map = [
            'admin' => 'is_admin',
            'sales_manager' => 'is_sales_manager',
            'bankdar' => 'is_bankdar',
            'representative' => 'is_representative',
            'author' => 'is_author',
        ];

        foreach ($map as $role => $flag) {
            if (filter_var($data[$flag] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * فقط یک نقش staff روشن می‌شود.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyStaffRoleFlags(array $data, ?string $role): array
    {
        $data['is_admin'] = $role === 'admin';
        $data['is_author'] = $role === 'author';
        $data['is_representative'] = $role === 'representative';
        $data['is_sales_manager'] = $role === 'sales_manager';
        $data['is_bankdar'] = $role === 'bankdar';

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    public static function normalizeUserKind(array $data): array
    {
        if (($data['user_kind'] ?? 'customer') === 'customer') {
            $data = static::applyStaffRoleFlags($data, null);
        }

        unset($data['user_kind'], $data['staff_role']);

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    public static function stripVirtualAccessFormKeys(array $data): array
    {
        unset(
            $data['user_kind'],
            $data['staff_role'],
            $data['rep_sales_manager_id'],
            $data['rep_province_id'],
            $data['rep_city_id'],
            $data['rep_max_active_reservations'],
            $data['locked_province'],
            $data['bankdar_create_kind'],
        );

        return $data;
    }
}
