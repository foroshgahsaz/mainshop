<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\UserResource;
use App\Support\AdminAccess;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends AdminListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'کاربران';

    protected function adminListTitle(): ?string
    {
        return 'لیست کاربران';
    }

    public function getDefaultActiveTab(): string|int|null
    {
        if (AdminAccess::isSalesManagerOnly()) {
            return 'representatives';
        }

        return 'customers';
    }

    public function getTabs(): array
    {
        if (AdminAccess::isSalesManagerOnly()) {
            return [
                'representatives' => Tab::make('نمایندگان')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('is_representative', true)),
                'rep_customers' => Tab::make('مشتریان نمایندگان')
                    ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('created_by_representative_id')),
            ];
        }

        return [
            'customers' => Tab::make('مشتریان')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('is_admin', false)
                    ->where('is_author', false)
                    ->where('is_representative', false)
                    ->where('is_sales_manager', false)),
            'staff' => Tab::make('غیر مشتری')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(
                    fn (Builder $q) => $q
                        ->where('is_admin', true)
                        ->orWhere('is_author', true)
                        ->orWhere('is_representative', true)
                        ->orWhere('is_sales_manager', true)
                )),
        ];
    }

    protected function getHeaderActions(): array
    {
        if (! AdminAccess::canManageShopInAdmin()) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('افزودن کاربر')
                ->icon('heroicon-o-plus')
                ->extraAttributes(['class' => 'fi-admin-btn-create']),
        ];
    }
}
