<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\Pages\EditRecord as BaseEditRecord;
use App\Filament\Support\ProductDeleteActions;

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
            ProductDeleteActions::pageDeleteAction(),
        ];
    }
}
