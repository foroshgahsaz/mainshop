<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Form;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return static::applyAccessFieldsFromForm($this->form, $data);
    }

    protected function afterCreate(): void
    {
        if (! $this->record->is_representative) {
            return;
        }

        $raw = $this->form->getRawState();

        $this->record->representativeProfile()->create([
            'province_id' => $raw['rep_province_id'] ?? null,
            'city_id' => $raw['rep_city_id'] ?? null,
            'max_active_reservations' => (int) ($raw['rep_max_active_reservations'] ?? 3),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public static function applyAccessFieldsFromForm(Form $form, array $data): array
    {
        $raw = $form->getRawState();

        $userKind = $raw['user_kind'] ?? $data['user_kind'] ?? 'customer';
        $data['user_kind'] = $userKind;

        if ($userKind === 'staff') {
            $data['is_admin'] = (bool) ($raw['is_admin'] ?? $data['is_admin'] ?? false);
            $data['is_author'] = (bool) ($raw['is_author'] ?? $data['is_author'] ?? false);
            $data['is_representative'] = (bool) ($raw['is_representative'] ?? $data['is_representative'] ?? false);
        }

        return static::normalizeUserKind($data);
    }

    /** @param  array<string, mixed>  $data */
    public static function resolveUserKindFromFlags(array $data): string
    {
        foreach (['is_admin', 'is_author', 'is_representative'] as $flag) {
            if (filter_var($data[$flag] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return 'staff';
            }
        }

        return 'customer';
    }

    /** @param  array<string, mixed>  $data */
    public static function normalizeUserKind(array $data): array
    {
        if (($data['user_kind'] ?? 'customer') === 'customer') {
            $data['is_admin'] = false;
            $data['is_author'] = false;
            $data['is_representative'] = false;
        }

        unset($data['user_kind']);

        return $data;
    }
}
