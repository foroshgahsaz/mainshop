<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Support\AdminTable;
use App\Filament\Support\FinancialTableFilters;
use App\Filament\Support\UserTableColumns;
use App\Filament\Support\OrderDeleteActions;
use App\Models\Order;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
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

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function canCreate(): bool
    {
        return AdminAccess::canCreateOrdersInAdmin();
    }

    public static function canDelete($record): bool
    {
        return AdminAccess::canSoftDeleteShopRecordsInAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return AdminAccess::canSoftDeleteShopRecordsInAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'representative', 'payments', 'creditPlan']);

        // ادمین سیستم می‌تواند حذف‌شده‌ها را با فیلتر ببیند؛ بنکدار/نماینده soft-deleted را نمی‌بینند.
        if (AdminAccess::isFullAdmin()) {
            $query = $query->withTrashed();
        }

        if (AdminAccess::isLimitedStaffOnly()) {
            $query = app(SalesRegionService::class)->scopeOrdersManagedBy($query, (int) auth()->id());
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->recordUrl(fn (Order $record): string => static::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('کد سفارش')
                    ->searchable()
                    ->copyable()
                    ->weight('medium')
                    ->color('primary'),
                UserTableColumns::nameOnly(),
                Tables\Columns\TextColumn::make('representative.name')
                    ->label('نماینده')
                    ->placeholder('—')
                    ->formatStateUsing(fn (Order $record): string => $record->representativeLabel())
                    ->toggleable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('مبلغ')
                    ->alignEnd()
                    ->formatStateUsing(fn (?int $state) => ShopLabels::formatMoney($state)),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('روش پرداخت')
                    ->badge()
                    ->formatStateUsing(fn (Order $record): string => $record->paymentMethodLabel()),
                Tables\Columns\TextColumn::make('creditPlan.name')
                    ->label('طرح اعتباری')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('latest_success_payment_tracking')
                    ->label('پیگیری پرداخت')
                    ->state(function (Order $record): string {
                        $payment = $record->payments
                            ->where('status', \App\Models\Payment::STATUS_SUCCESS)
                            ->sortByDesc('paid_at')
                            ->first();

                        return $payment?->tracking_code ?? '—';
                    })
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ShopLabels::orderStatus($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->shopJalaliDateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_gateway')
                    ->label('روش پرداخت')
                    ->options(fn (): array => static::paymentGatewayFilterOptions())
                    ->query(fn (Builder $query, array $data): Builder => static::applyPaymentGatewayFilter(
                        $query,
                        $data['value'] ?? null,
                    )),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(static::statusFilterOptions()),
                FinancialTableFilters::orderHasSuccessfulPaymentFilter(),
                FinancialTableFilters::orderCreditPlanFilter(),
                FinancialTableFilters::orderGatewayTypeFilter(),
                FinancialTableFilters::orderCreatedDateFilter(),
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn (): bool => AdminAccess::isFullAdmin()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('جزئیات')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->button()
                    ->outlined(),
                Tables\Actions\RestoreAction::make()
                    ->label('بازیابی')
                    ->visible(fn (Order $record): bool => AdminAccess::isFullAdmin() && $record->trashed()),
                OrderDeleteActions::tableDeleteAction(),
            ])
            ->actionsColumnLabel('عملیات')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    OrderDeleteActions::tableBulkDeleteAction(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn (): bool => AdminAccess::isFullAdmin()),
                ]),
            ]);
    }

    /** @return array<string, string> */
    protected static function paymentGatewayFilterOptions(): array
    {
        $options = [];

        foreach (app(PaymentGatewayCatalog::class)->enabled() as $gateway) {
            $name = (string) ($gateway['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $options[$name] = (string) ($gateway['label'] ?? ShopLabels::gateway($name));
        }

        if (app(PaymentGatewayCatalog::class)->codEnabled()) {
            $options['cod'] = ShopLabels::paymentMethod('cod');
        }

        return $options;
    }

    /**
     * فیلترهای لیست سفارش (ادمین) — برای خروجی اکسل و هم‌خوانی با جدول Filament.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function applyListTableFiltersToQuery(Builder $query, array $filters): Builder
    {
        $status = $filters['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $gateway = $filters['payment_gateway'] ?? null;
        if (is_string($gateway) && $gateway !== '') {
            $query = static::applyPaymentGatewayFilter($query, $gateway);
        }

        $creditPlan = $filters['credit_plan_id'] ?? null;
        if (filled($creditPlan)) {
            $query->where('credit_plan_id', (int) $creditPlan);
        }

        $gatewayType = $filters['payment_gateway_type'] ?? null;
        if (is_string($gatewayType) && $gatewayType !== '') {
            $query = FinancialTableFilters::applyOrderGatewayTypeFilter($query, $gatewayType);
        }

        if (($filters['has_successful_payment'] ?? null) === true || $filters['has_successful_payment'] === '1') {
            $query->whereHas(
                'payments',
                fn (Builder $payments): Builder => $payments->where('status', \App\Models\Payment::STATUS_SUCCESS)
            );
        } elseif (($filters['has_successful_payment'] ?? null) === false || $filters['has_successful_payment'] === '0') {
            $query->whereDoesntHave(
                'payments',
                fn (Builder $payments): Builder => $payments->where('status', \App\Models\Payment::STATUS_SUCCESS)
            );
        }

        $from = $filters['created_from'] ?? $filters['from'] ?? null;
        $until = $filters['created_until'] ?? $filters['until'] ?? null;
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($until) {
            $query->whereDate('created_at', '<=', $until);
        }

        $trashed = $filters['trashed'] ?? null;
        if ($trashed === 'only') {
            $query->onlyTrashed();
        } elseif ($trashed !== 'with' && AdminAccess::isFullAdmin()) {
            $query->whereNull($query->getModel()->getQualifiedDeletedAtColumn());
        }

        return $query;
    }

    protected static function applyPaymentGatewayFilter(Builder $query, mixed $gateway): Builder
    {
        if (! is_string($gateway) || $gateway === '') {
            return $query;
        }

        if ($gateway === 'cod') {
            return $query->where('payment_method', 'cod');
        }

        return $query->where(function (Builder $inner) use ($gateway): void {
            $inner->where('payment_method', $gateway)
                ->orWhere(function (Builder $online) use ($gateway): void {
                    $online->where('payment_method', 'online')
                        ->whereHas('payments', fn (Builder $payments): Builder => $payments->where('gateway', $gateway));
                });
        });
    }

    /** @return array<string, string> */
    protected static function statusFilterOptions(): array
    {
        return [
            Order::STATUS_DRAFT => ShopLabels::orderStatus(Order::STATUS_DRAFT),
            Order::STATUS_PROFORMA => ShopLabels::orderStatus(Order::STATUS_PROFORMA),
            Order::STATUS_PENDING => ShopLabels::orderStatus(Order::STATUS_PENDING),
            Order::STATUS_PROCESSING => ShopLabels::orderStatus(Order::STATUS_PROCESSING),
            Order::STATUS_WAREHOUSE_PROCESSING => ShopLabels::orderStatus(Order::STATUS_WAREHOUSE_PROCESSING),
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
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
