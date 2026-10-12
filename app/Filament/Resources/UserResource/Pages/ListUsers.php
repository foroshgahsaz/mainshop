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
                    ->modifyQueryUsing(fn (Builder $query) => $query
                        ->where('is_representative', true)
                        ->whereHas(
                            'representativeProfile',
                            fn (Builder $q) => $q->where('sales_manager_id', auth()->id())
                        )),
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
            'staff' => Tab::make('مدیران')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(
                    fn (Builder $q) => $q
                        ->where('is_admin', true)
                        ->orWhere('is_author', true)
                        ->orWhere('is_representative', true)
                        ->orWhere('is_sales_manager', true)
                )),
        ];
    }

    public static function topBarCreateLabel(): ?string
    {
        return AdminAccess::isSalesManagerOnly() ? 'افزودن نماینده' : 'افزودن کاربر';
    }

    public static function canShowTopBarCreate(): bool
    {
        return AdminAccess::canCreateRepresentativesInAdmin();
    }

    protected function extraListHeaderActions(): array
    {
        if (! AdminAccess::isSalesManagerOnly()) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('افزودن نماینده')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
