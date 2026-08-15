<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'کاربران';

    public function getDefaultActiveTab(): string|int|null
    {
        return 'customers';
    }

    public function getTabs(): array
    {
        return [
            'customers' => Tab::make('مشتریان')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('is_admin', false)
                    ->where('is_author', false)),
            'staff' => Tab::make('غیر مشتری')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(
                    fn (Builder $q) => $q->where('is_admin', true)->orWhere('is_author', true)
                )),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('افزودن کاربر')
                ->icon('heroicon-o-plus'),
        ];
    }
}
