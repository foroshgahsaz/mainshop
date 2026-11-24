<?php

namespace App\Filament\Support;

use App\Models\CreditPlan;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayCatalog;
use Filament\Forms;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

final class FinancialTableFilters
{
    public static function orderCreatedDateFilter(string $column = 'created_at'): Filter
    {
        return Filter::make('created_at_range')
            ->label('بازه تاریخ')
            ->form([
                Forms\Components\DatePicker::make('from')->label('از تاریخ'),
                Forms\Components\DatePicker::make('until')->label('تا تاریخ'),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                $from = $data['from'] ?? null;
                $until = $data['until'] ?? null;

                if ($from) {
                    $query->whereDate($column, '>=', $from);
                }

                if ($until) {
                    $query->whereDate($column, '<=', $until);
                }

                return $query;
            })
            ->indicateUsing(function (array $data): array {
                $labels = [];
                if (! empty($data['from'])) {
                    $labels[] = 'از '.$data['from'];
                }
                if (! empty($data['until'])) {
                    $labels[] = 'تا '.$data['until'];
                }

                return $labels !== [] ? ['بازه: '.implode(' — ', $labels)] : [];
            });
    }

    public static function orderCreditPlanFilter(): SelectFilter
    {
        return SelectFilter::make('credit_plan_id')
            ->label('طرح اعتباری')
            ->options(fn (): array => CreditPlan::query()->orderBy('name')->pluck('name', 'id')->all())
            ->searchable();
    }

    public static function orderHasSuccessfulPaymentFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_successful_payment')
            ->label('پرداخت موفق')
            ->queries(
                true: fn (Builder $query): Builder => $query->whereHas(
                    'payments',
                    fn (Builder $payments): Builder => $payments->where('status', Payment::STATUS_SUCCESS)
                ),
                false: fn (Builder $query): Builder => $query->whereDoesntHave(
                    'payments',
                    fn (Builder $payments): Builder => $payments->where('status', Payment::STATUS_SUCCESS)
                ),
                blank: fn (Builder $query): Builder => $query,
            );
    }

    public static function orderGatewayTypeFilter(): SelectFilter
    {
        return SelectFilter::make('payment_gateway_type')
            ->label('نوع پرداخت (اعتباری/نقدی)')
            ->options([
                PaymentGatewayCatalog::TYPE_CREDIT => 'اعتباری',
                PaymentGatewayCatalog::TYPE_CASH => 'نقدی',
            ])
            ->query(fn (Builder $query, array $data): Builder => static::applyOrderGatewayTypeFilter(
                $query,
                $data['value'] ?? null,
            ));
    }

    public static function applyOrderGatewayTypeFilter(Builder $query, mixed $type): Builder
    {
        if (! is_string($type) || $type === '') {
            return $query;
        }

        $gateways = static::gatewaySlugsForType($type);
        if ($gateways === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'payments',
            fn (Builder $payments): Builder => $payments
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereIn('gateway', $gateways)
        );
    }

    public static function paymentCreditPlanFilter(): SelectFilter
    {
        return SelectFilter::make('credit_plan_id')
            ->label('طرح اعتباری سفارش')
            ->options(fn (): array => CreditPlan::query()->orderBy('name')->pluck('name', 'id')->all())
            ->query(function (Builder $query, array $data): Builder {
                $planId = $data['value'] ?? null;
                if (! filled($planId)) {
                    return $query;
                }

                return $query->whereHas(
                    'order',
                    fn (Builder $order): Builder => $order->where('credit_plan_id', (int) $planId)
                );
            })
            ->searchable();
    }

    public static function paymentGatewayTypeFilter(): SelectFilter
    {
        return SelectFilter::make('gateway_type')
            ->label('نوع درگاه')
            ->options([
                PaymentGatewayCatalog::TYPE_CREDIT => 'اعتباری',
                PaymentGatewayCatalog::TYPE_CASH => 'نقدی',
            ])
            ->query(fn (Builder $query, array $data): Builder => static::applyPaymentGatewayTypeFilter(
                $query,
                $data['value'] ?? null,
            ));
    }

    public static function applyPaymentGatewayTypeFilter(Builder $query, mixed $type): Builder
    {
        if (! is_string($type) || $type === '') {
            return $query;
        }

        $gateways = static::gatewaySlugsForType($type);
        if ($gateways === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('gateway', $gateways);
    }

    public static function paymentPaidDateFilter(): Filter
    {
        return Filter::make('paid_at_range')
            ->label('بازه تاریخ پرداخت')
            ->form([
                Forms\Components\DatePicker::make('from')->label('از تاریخ'),
                Forms\Components\DatePicker::make('until')->label('تا تاریخ'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                $from = $data['from'] ?? null;
                $until = $data['until'] ?? null;

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
            });
    }

    /** @return list<string> */
    public static function gatewaySlugsForType(string $type): array
    {
        $gateways = [];

        foreach (array_keys(config('payment.gateways', [])) as $name) {
            $configured = config("payment.gateways.{$name}.type", PaymentGatewayCatalog::TYPE_CASH);
            if ($configured === $type) {
                $gateways[] = $name;
            }
        }

        return $gateways;
    }

    public static function gatewayTypeLabel(?string $gateway): string
    {
        if ($gateway === null || $gateway === '' || $gateway === 'cod') {
            return $gateway === 'cod' ? 'نقدی (در محل)' : '—';
        }

        $type = config("payment.gateways.{$gateway}.type", PaymentGatewayCatalog::TYPE_CASH);

        return $type === PaymentGatewayCatalog::TYPE_CREDIT ? 'اعتباری' : 'نقدی';
    }
}