<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_kind'] = (! empty($data['is_admin']) || ! empty($data['is_author']))
            ? 'staff'
            : 'customer';

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_kind'] = $this->form->getState()['user_kind'] ?? 'customer';

        return CreateUser::normalizeUserKind($data);
    }
}
