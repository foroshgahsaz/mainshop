<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\AdminListHeader;
use Filament\Actions\Action;
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

    public static function topBarCreateLabel(): ?string
    {
        return null;
    }

    public static function canShowTopBarCreate(): bool
    {
        return static::getResource()::canCreate();
    }

    /**
     * @return array<int, Action>
     */
    protected function extraListHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return $this->extraListHeaderActions();
    }

    public function getHeader(): ?View
    {
        $resource = static::getResource();

        return view('filament.partials.admin-list-header', [
            'icon' => $this->adminListIcon() ?? AdminListHeader::icon($resource),
            'section' => $this->adminListSection() ?? AdminListHeader::sectionLabel($resource),
            'title' => $this->adminListTitle() ?? AdminListHeader::listTitle($resource),
        ]);
    }
}
