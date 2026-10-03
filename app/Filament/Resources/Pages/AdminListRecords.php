<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Support\AdminListHeader;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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

    protected function listCreateActionLabel(): ?string
    {
        return null;
    }

    /**
     * @return array<int, Action>
     */
    protected function extraListHeaderActions(): array
    {
        return [];
    }

    protected function makeListCreateAction(): ?CreateAction
    {
        $resource = static::getResource();

        if (! $resource::canCreate() || ! $resource::hasPage('create')) {
            return null;
        }

        return CreateAction::make()
            ->label($this->listCreateActionLabel() ?? AdminListHeader::createTitle($resource))
            ->icon('heroicon-o-plus')
            ->extraAttributes(['class' => 'fi-admin-btn-create']);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $actions = $this->extraListHeaderActions();

        $create = $this->makeListCreateAction();
        if ($create) {
            array_unshift($actions, $create);
        }

        return $actions;
    }

    protected function configureAction(Action $action): void
    {
        parent::configureAction($action);

        if ($action instanceof CreateAction) {
            $action->extraAttributes([
                'class' => 'fi-admin-btn-create',
            ]);

            if (blank($action->getLabel())) {
                $action->label(AdminListHeader::createTitle(static::getResource()));
            }

            if (blank($action->getIcon())) {
                $action->icon('heroicon-o-plus');
            }
        }
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
