<?php

namespace App\Filament\Resources\MegaMenuLinkResource\Pages;

use App\Filament\Resources\MegaMenuLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMegaMenuLinks extends ListRecords
{
    protected static string $resource = MegaMenuLinkResource::class;

    public function mount(): void
    {
        abort_unless(MegaMenuLinkResource::schemaReady(), 404);

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('افزودن لینک'),
        ];
    }
}
