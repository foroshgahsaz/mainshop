<?php

namespace App\Filament\Representative\Resources;

use App\Filament\Representative\Pages\CreateDraftOrder;
use App\Filament\Representative\Resources\DraftOrderResource\Pages;
use App\Models\Order;
use App\Support\ShopFormatter;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DraftOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'پیش‌سفارش‌ها';

    protected static ?string $modelLabel = 'پیش‌سفارش';

    protected static ?string $pluralModelLabel = 'پیش‌سفارش‌ها';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('کد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('مشتری')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('اقلام')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn (int $state): string => ShopFormatter::money($state)),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین تغییر')
                    ->since(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('continue')
                    ->label('ادامه')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Order $record): string => CreateDraftOrder::getUrl(panel: 'representative').'?order='.$record->id),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('representative_id', auth()->id())
            ->where('status', Order::STATUS_DRAFT)
            ->withCount('items');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDraftOrders::route('/'),
        ];
    }
}
