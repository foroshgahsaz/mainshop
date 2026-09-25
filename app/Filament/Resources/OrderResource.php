<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\Order;
use App\Support\ShopLabels;
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
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')->label('کد سفارش')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.name')->label('کاربر')->placeholder('—'),
                Tables\Columns\TextColumn::make('user.phone')->label('موبایل')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn (?int $state) => ShopLabels::formatMoney($state)),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('روش پرداخت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ShopLabels::paymentMethod($state)),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ShopLabels::orderStatus($state)),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('روش پرداخت')
                    ->options(static::paymentMethodFilterOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(static::statusFilterOptions()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('جزئیات'),
            ]);
    }

    /** @return array<string, string> */
    protected static function paymentMethodFilterOptions(): array
    {
        return [
            'online' => ShopLabels::paymentMethod('online'),
            'cod' => ShopLabels::paymentMethod('cod'),
        ];
    }

    /** @return array<string, string> */
    protected static function statusFilterOptions(): array
    {
        return [
            Order::STATUS_PENDING => ShopLabels::orderStatus(Order::STATUS_PENDING),
            Order::STATUS_PROCESSING => ShopLabels::orderStatus(Order::STATUS_PROCESSING),
            Order::STATUS_SHIPPED => ShopLabels::orderStatus(Order::STATUS_SHIPPED),
            Order::STATUS_DELIVERED => ShopLabels::orderStatus(Order::STATUS_DELIVERED),
            Order::STATUS_CANCELED => ShopLabels::orderStatus(Order::STATUS_CANCELED),
            Order::STATUS_RETURNED => ShopLabels::orderStatus(Order::STATUS_RETURNED),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
