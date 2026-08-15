<?php

namespace App\Filament\Support;

use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class AdminTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateHeading('موردی یافت نشد')
            ->emptyStateDescription('برای شروع، یک رکورد جدید ایجاد کنید.')
            ->searchPlaceholder('جستجو...')
            ->filtersFormColumns(1)
            ->filtersFormWidth(MaxWidth::Medium)
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('فیلترها')
                    ->icon('heroicon-o-funnel')
                    ->slideOver()
                    ->modalWidth(MaxWidth::Medium)
            );
    }
}
