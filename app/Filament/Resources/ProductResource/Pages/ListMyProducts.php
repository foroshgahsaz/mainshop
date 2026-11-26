<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Support\AdminAccess;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListMyProducts extends ListProducts
{
    protected static ?string $title = 'محصولات من';

    protected static ?string $navigationLabel = 'محصولات من';

    public static function canAccess(array $parameters = []): bool
    {
        return AdminAccess::isSalesManagerOnly();
    }

    public function getHeading(): string
    {
        return 'محصولات من';
    }

    public function getSubheading(): ?string
    {
        return 'محصولاتی که شما ایجاد کرده‌اید — آخرین ویرایش‌کننده در جدول نمایش داده می‌شود.';
    }

    public function table(Table $table): Table
    {
        return static::getResource()::table($table)
            ->modifyQueryUsing(function (Builder $query): Builder {
                $userId = auth()->id();

                if ($userId === null) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where('created_by_user_id', (int) $userId);
            });
    }
}