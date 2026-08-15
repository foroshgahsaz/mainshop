<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\Pages\EditRecord as BaseEditRecord;
use Filament\Actions;

class EditProduct extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;

    public function hasCombinedRelationManagerTabsWithContentForm(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف')
                ->successNotificationTitle('حذف شد'),
        ];
    }
}
