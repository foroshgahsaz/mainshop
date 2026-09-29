<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_kind'] = $this->form->getState()['user_kind'] ?? 'customer';

        return static::normalizeUserKind($data);
    }

    protected function afterCreate(): void
    {
        if (! $this->record->is_representative) {
            return;
        }

        $state = $this->form->getState();

        $this->record->representativeProfile()->create([
            'province_id' => $state['rep_province_id'] ?? null,
            'city_id' => $state['rep_city_id'] ?? null,
            'max_active_reservations' => (int) ($state['rep_max_active_reservations'] ?? 3),
        ]);
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
