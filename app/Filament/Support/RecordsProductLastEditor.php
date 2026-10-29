<?php

namespace App\Filament\Support;

use App\Support\SlugFromTitle;

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

        $data = parent::mutateFormDataBeforeSave($data);

        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));

        if ($name !== '' && ($slug === '' || str_starts_with($slug, 'draft-'))) {
            $data['slug'] = SlugFromTitle::fromProductName($name);
        }

        return $data;
    }
}
