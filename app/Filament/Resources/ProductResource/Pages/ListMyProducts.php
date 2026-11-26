<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Services\Product\SalesManagerMyProductsQuery;
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
        return 'محصولات ثبت‌شده توسط شما؛ ردیف‌های قدیمی بدون ایجادکننده هم اگر آخرین ویرایش‌شان شما باشد اینجا می‌آیند.';
    }

    public function table(Table $table): Table
    {
        return static::getResource()::table($table)
            ->modifyQueryUsing(function (Builder $query): Builder {
                $userId = auth()->id();

                if ($userId === null) {
                    return $query->whereRaw('1 = 0');
                }

                return SalesManagerMyProductsQuery::apply($query, (int) $userId);
            });
    }
}