<?php

namespace App\Filament\Resources\MegaMenuLinkResource\Pages;

use App\Filament\Resources\MegaMenuLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMegaMenuLink extends CreateRecord
{
    protected static string $resource = MegaMenuLinkResource::class;

    public function mount(): void
    {
        abort_unless(MegaMenuLinkResource::schemaReady(), 404);

        parent::mount();
    }
}
