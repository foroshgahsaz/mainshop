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

    protected static ?string $navigationLabel = 'پیش‌فاکتورها';

    protected static ?string $modelLabel = 'پیش‌فاکتور';

    protected static ?string $pluralModelLabel = 'پیش‌فاکتورها';

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
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Order::STATUS_DRAFT => 'در حال تکمیل',
                        Order::STATUS_PROFORMA => 'ثبت‌شده',
                        default => $state,
                    }),
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
                    ->label('ادامه ویرایش')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Order $record): bool => $record->isDraft())
                    ->url(fn (Order $record): string => CreateDraftOrder::getUrl(panel: 'representative').'?order='.$record->id),
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده')
                    ->visible(fn (Order $record): bool => $record->isProforma()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('representative_id', auth()->id())
            ->whereIn('status', [Order::STATUS_DRAFT, Order::STATUS_PROFORMA])
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
            'view' => Pages\ViewDraftOrder::route('/{record}'),
        ];
    }

    public static function canView($record): bool
    {
        return $record instanceof Order
            && $record->representative_id === auth()->id()
            && ($record->isDraft() || $record->isProforma());
    }
}
