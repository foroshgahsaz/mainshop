<?php

namespace App\Filament\Support;

trait RecordsProductLastEditor
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $userId = auth()->id();
        if ($userId !== null) {
            $data['last_edited_by_user_id'] = $userId;
        }

        return parent::mutateFormDataBeforeSave($data);
    }
}
