<?php

namespace App\Filament\Resources\Pages\Concerns;

use App\Filament\Support\AdminListHeader;
use Illuminate\Contracts\View\View;

trait ProvidesAdminFormHeader
{
    protected function adminFormIcon(): ?string
    {
        return null;
    }

    protected function adminFormSection(): ?string
    {
        return null;
    }

    protected function adminFormTitle(): ?string
    {
        return null;
    }

    abstract protected function defaultAdminFormTitle(): string;

    public function getHeader(): ?View
    {
        $resource = static::getResource();

        $title = $this->adminFormTitle();
        if ($title === null) {
            $title = filled(static::$title ?? null)
                ? (string) static::$title
                : $this->defaultAdminFormTitle();
        }

        return view('filament.partials.admin-list-header', [
            'icon' => $this->adminFormIcon() ?? AdminListHeader::icon($resource),
            'section' => $this->adminFormSection() ?? AdminListHeader::sectionLabel($resource),
            'title' => $title,
            'actions' => $this->getCachedHeaderActions(),
        ]);
    }
}
