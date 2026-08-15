<?php

namespace App\Filament\Support\Filters;

use Filament\Forms;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PriceRangeFilter
{
    public static function make(string $column = 'price', int $max = 10000000): Filter
    {
        return Filter::make($column.'_range')
            ->label('محدوده قیمت')
            ->form([
                Forms\Components\View::make('filament.tables.filters.price-range-slider')
                    ->viewData(['max' => $max]),
                Forms\Components\TextInput::make('min')
                    ->label('حداقل قیمت (تومان)')
                    ->numeric()
                    ->extraInputAttributes(['class' => 'price-range-min-input', 'data-price-range' => 'min']),
                Forms\Components\TextInput::make('max')
                    ->label('حداکثر قیمت (تومان)')
                    ->numeric()
                    ->extraInputAttributes(['class' => 'price-range-max-input', 'data-price-range' => 'max']),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when(filled($data['min'] ?? null), fn ($q) => $q->where($column, '>=', (int) $data['min']))
                    ->when(filled($data['max'] ?? null), fn ($q) => $q->where($column, '<=', (int) $data['max']));
            })
            ->indicateUsing(function (array $data): ?string {
                if (blank($data['min'] ?? null) && blank($data['max'] ?? null)) {
                    return null;
                }

                $min = filled($data['min'] ?? null) ? number_format((int) $data['min']) : '۰';
                $max = filled($data['max'] ?? null) ? number_format((int) $data['max']) : '∞';

                return "قیمت: {$min} – {$max} تومان";
            });
    }
}
