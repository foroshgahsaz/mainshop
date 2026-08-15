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

    /** @param  array<string, mixed>  $data */
    public static function normalizeUserKind(array $data): array
    {
        if (($data['user_kind'] ?? 'customer') === 'customer') {
            $data['is_admin'] = false;
            $data['is_author'] = false;
        }

        unset($data['user_kind']);

        return $data;
    }
}
