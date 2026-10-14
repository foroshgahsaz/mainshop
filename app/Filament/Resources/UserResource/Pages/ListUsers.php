<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\Representative\SalesRegionService;
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

        return User::ADMIN_LIST_SEGMENT_CUSTOMER;
    }

    public function getTabs(): array
    {
        if (AdminAccess::isSalesManagerOnly()) {
            return [
                'representatives' => Tab::make('نمایندگان من')
                    ->modifyQueryUsing(fn (Builder $query) => app(SalesRegionService::class)
                        ->scopeRepresentativesManagedBy($query, (int) auth()->id())),
                'rep_customers' => Tab::make('مشتریان نمایندگان من')
                    ->modifyQueryUsing(fn (Builder $query) => app(SalesRegionService::class)
                        ->scopeCustomersOfManagedRepresentatives($query, (int) auth()->id())),
            ];
        }

        return [
            User::ADMIN_LIST_SEGMENT_CUSTOMER => Tab::make('مشتریان')
                ->modifyQueryUsing(fn (Builder $query) => $query->adminListSegment(User::ADMIN_LIST_SEGMENT_CUSTOMER)),
            User::ADMIN_LIST_SEGMENT_SALES_MANAGER => Tab::make('مدیران مناطق')
                ->modifyQueryUsing(fn (Builder $query) => $query->adminListSegment(User::ADMIN_LIST_SEGMENT_SALES_MANAGER)),
            User::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN => Tab::make('مدیران سیستم')
                ->modifyQueryUsing(fn (Builder $query) => $query->adminListSegment(User::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN)),
            User::ADMIN_LIST_SEGMENT_REPRESENTATIVE => Tab::make('نمایندگان')
                ->modifyQueryUsing(fn (Builder $query) => $query->adminListSegment(User::ADMIN_LIST_SEGMENT_REPRESENTATIVE)),
            User::ADMIN_LIST_SEGMENT_AUTHOR => Tab::make('نویسندگان')
                ->modifyQueryUsing(fn (Builder $query) => $query->adminListSegment(User::ADMIN_LIST_SEGMENT_AUTHOR)),
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
