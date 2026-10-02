<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Concerns\ProvidesAdminFormHeader;
use App\Filament\Support\AdminListHeader;
use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Filament\Support\FileUploadStateNormalizer;
use App\Filament\Support\NormalizesFileUploadFormState;
use Filament\Notifications\Notification;

abstract class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    use NormalizesFileUploadFormState;
    use ProvidesAdminFormHeader;

    protected function defaultAdminFormTitle(): string
    {
        return AdminListHeader::createTitle(static::getResource());
    }
    protected function getCreatedNotification(): ?Notification
    {
        return CrudSuccessNotification::created();
    }

    protected function beforeValidate(): void
    {
        FileUploadSanitizer::sanitize($this, $this->form);
    }
}
