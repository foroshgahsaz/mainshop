<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\AdminListHeader;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

abstract class AdminListRecords extends ListRecords
{
    protected function adminListIcon(): ?string
    {
        return null;
    }

    protected function adminListSection(): ?string
    {
        return null;
    }

    protected function adminListTitle(): ?string
    {
        return null;
    }

    public function getHeader(): ?View
    {
        $resource = static::getResource();

        return view('filament.partials.admin-list-header', [
            'icon' => $this->adminListIcon() ?? AdminListHeader::icon($resource),
            'section' => $this->adminListSection() ?? AdminListHeader::sectionLabel($resource),
            'title' => $this->adminListTitle() ?? AdminListHeader::listTitle($resource),
            'actions' => $this->getCachedHeaderActions(),
        ]);
    }
}
