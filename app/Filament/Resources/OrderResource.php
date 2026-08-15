<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'سفارش‌ها';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')->label('کد سفارش')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.name')->label('کاربر')->placeholder('—'),
                Tables\Columns\TextColumn::make('user.phone')->label('موبایل')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('final_amount')->label('مبلغ')->numeric()->suffix(' تومان'),
                Tables\Columns\TextColumn::make('payment_method')->label('روش پرداخت')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'online' => 'آنلاین',
                        'cod' => 'در محل',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('جزئیات'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
