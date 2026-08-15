<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\CrudSuccessNotification;
use Filament\Notifications\Notification;

abstract class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected function getSavedNotification(): ?Notification
    {
        return CrudSuccessNotification::saved();
    }
}
