<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Filament\Support\MissingUploadPathCleaner;
use Filament\Notifications\Notification;

abstract class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected function getSavedNotification(): ?Notification
    {
        return CrudSuccessNotification::saved();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return MissingUploadPathCleaner::clearFromFormData(
            parent::mutateFormDataBeforeFill($data),
            $this->getRecord(),
        );
    }

    protected function afterFill(): void
    {
        FileUploadSanitizer::sanitize($this, $this->form, $this->getRecord());
    }

    protected function beforeValidate(): void
    {
        FileUploadSanitizer::sanitize($this, $this->form, $this->getRecord());
    }
}
