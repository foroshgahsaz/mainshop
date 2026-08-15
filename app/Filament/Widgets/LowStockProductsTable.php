<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProductsTable extends BaseWidget
{
    protected static ?string $heading = 'هشدار موجودی کم';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->where('stock', '<=', 5)
                    ->orderBy('stock')
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('محصول')
                    ->limit(30),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('موجودی')
                    ->badge()
                    ->color(fn (int $state): string => $state <= 0 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت')
                    ->numeric()
                    ->suffix(' تومان'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('ویرایش')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('موجودی همه محصولات مناسب است')
            ->paginated(false);
    }
}
