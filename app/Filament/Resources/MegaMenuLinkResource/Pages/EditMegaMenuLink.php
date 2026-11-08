<?php

namespace App\Filament\Resources\MegaMenuLinkResource\Pages;

use App\Filament\Resources\MegaMenuLinkResource;
use Filament\Resources\Pages\EditRecord;

class EditMegaMenuLink extends EditRecord
{
    protected static string $resource = MegaMenuLinkResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(MegaMenuLinkResource::schemaReady(), 404);

        parent::mount($record);
    }
}
