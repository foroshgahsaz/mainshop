<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Support\AdminTable;
use App\Filament\Support\PaymentDeleteActions;
use App\Models\Order;
use App\Models\Payment;
use App\Support\ShopLabels;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'پرداخت‌ها';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order', 'user']);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')->label('پیگیری')->searchable(),
                Tables\Columns\TextColumn::make('order.tracking_code')->label('سفارش'),
                Tables\Columns\TextColumn::make('user.name')->label('کاربر'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn (?int $state) => ShopLabels::formatMoney($state)),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('درگاه')
                    ->formatStateUsing(fn (?string $state) => ShopLabels::gateway($state)),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ShopLabels::paymentStatus($state)),
                Tables\Columns\TextColumn::make('paid_at')->label('تاریخ پرداخت')->dateTime('Y/m/d H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gateway')
                    ->label('درگاه')
                    ->options(static::gatewayFilterOptions()),
                Tables\Filters\SelectFilter::make('order_status')
                    ->label('وضعیت سفارش')
                    ->options(static::orderStatusFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (! filled($status)) {
                            return $query;
                        }

                        return $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', $status));
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت پرداخت')
                    ->options(static::paymentStatusFilterOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('جزئیات'),
                PaymentDeleteActions::tableDeleteAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    PaymentDeleteActions::tableBulkDeleteAction(),
                ]),
            ]);
    }

    /** @return array<string, string> */
    protected static function gatewayFilterOptions(): array
    {
        $options = [];

        foreach (array_keys(config('payment.gateways', [])) as $gateway) {
            $options[$gateway] = ShopLabels::gateway($gateway);
        }

        return $options;
    }

    /** @return array<string, string> */
    protected static function orderStatusFilterOptions(): array
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

    /** @return array<string, string> */
    protected static function paymentStatusFilterOptions(): array
    {
        return [
            Payment::STATUS_PENDING => ShopLabels::paymentStatus(Payment::STATUS_PENDING),
            Payment::STATUS_SUCCESS => ShopLabels::paymentStatus(Payment::STATUS_SUCCESS),
            Payment::STATUS_FAILED => ShopLabels::paymentStatus(Payment::STATUS_FAILED),
            Payment::STATUS_CANCELED => ShopLabels::paymentStatus(Payment::STATUS_CANCELED),
            Payment::STATUS_REFUNDED => ShopLabels::paymentStatus(Payment::STATUS_REFUNDED),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
