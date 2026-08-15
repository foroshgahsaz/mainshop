<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\CrudSuccessNotification;
use Filament\Notifications\Notification;

abstract class CreateRecord extends \Filament\Resources\Pages\CreateRecord
{
    protected function getCreatedNotification(): ?Notification
    {
        return CrudSuccessNotification::created();
    }
}
