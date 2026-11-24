<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Support\AdminTable;
use App\Filament\Support\FinancialTableFilters;
use App\Filament\Support\UserTableColumns;
use App\Filament\Support\PaymentDeleteActions;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
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

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

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
        $query = parent::getEloquentQuery()->with(['order.creditPlan', 'user']);

        if (AdminAccess::isLimitedStaffOnly()) {
            $repIds = app(SalesRegionService::class)
                ->managedRepresentativeIds((int) auth()->id());

            $query->whereHas('order', function (Builder $q) use ($repIds): void {
                if ($repIds === []) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('representative_id', $repIds);
                }
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->recordUrl(fn (Payment $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')->label('پیگیری')->searchable(),
                Tables\Columns\TextColumn::make('order.tracking_code')->label('سفارش'),
                UserTableColumns::nameWithPhone(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn (?int $state) => ShopLabels::formatMoney($state)),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('درگاه')
                    ->formatStateUsing(fn (?string $state) => ShopLabels::gateway($state)),
                Tables\Columns\TextColumn::make('gateway_type')
                    ->label('نوع')
                    ->state(fn (Payment $record): string => FinancialTableFilters::gatewayTypeLabel($record->gateway))
                    ->badge(),
                Tables\Columns\TextColumn::make('order.creditPlan.name')
                    ->label('طرح اعتباری')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ShopLabels::paymentStatus($state)),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('تاریخ پرداخت')
                    ->shopJalaliDateTime()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->shopJalaliDateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                FinancialTableFilters::paymentGatewayTypeFilter(),
                FinancialTableFilters::paymentCreditPlanFilter(),
                FinancialTableFilters::paymentPaidDateFilter(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('جزئیات')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->button()
                    ->outlined(),
                PaymentDeleteActions::tableDeleteAction()
                    ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()),
            ])
            ->actionsColumnLabel('عملیات')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    PaymentDeleteActions::tableBulkDeleteAction()
                        ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()),
                ]),
            ]);
    }

    /**
     * فیلترهای لیست پرداخت — برای خروجی اکسل.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function applyListTableFiltersToQuery(Builder $query, array $filters): Builder
    {
        $gateway = $filters['gateway'] ?? null;
        if (is_string($gateway) && $gateway !== '') {
            $query->where('gateway', $gateway);
        }

        $status = $filters['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $orderStatus = $filters['order_status'] ?? null;
        if (is_string($orderStatus) && $orderStatus !== '') {
            $query->whereHas('order', fn (Builder $order): Builder => $order->where('status', $orderStatus));
        }

        $creditPlan = $filters['credit_plan_id'] ?? null;
        if (filled($creditPlan)) {
            $query->whereHas('order', fn (Builder $order): Builder => $order->where('credit_plan_id', (int) $creditPlan));
        }

        $gatewayType = $filters['gateway_type'] ?? null;
        if (is_string($gatewayType) && $gatewayType !== '') {
            $query = FinancialTableFilters::applyPaymentGatewayTypeFilter($query, $gatewayType);
        }

        $from = $filters['paid_from'] ?? $filters['from'] ?? null;
        $until = $filters['paid_until'] ?? $filters['until'] ?? null;
        if ($from) {
            $query->where(function (Builder $inner) use ($from): void {
                $inner->whereDate('paid_at', '>=', $from)
                    ->orWhere(function (Builder $fallback) use ($from): void {
                        $fallback->whereNull('paid_at')->whereDate('created_at', '>=', $from);
                    });
            });
        }
        if ($until) {
            $query->where(function (Builder $inner) use ($until): void {
                $inner->whereDate('paid_at', '<=', $until)
                    ->orWhere(function (Builder $fallback) use ($until): void {
                        $fallback->whereNull('paid_at')->whereDate('created_at', '<=', $until);
                    });
            });
        }

        return $query;
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
            Order::STATUS_WAREHOUSE_PROCESSING => ShopLabels::orderStatus(Order::STATUS_WAREHOUSE_PROCESSING),
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
