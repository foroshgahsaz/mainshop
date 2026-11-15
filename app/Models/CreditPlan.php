<?php

namespace App\Models;

use App\Services\Payment\PaymentGatewayCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CreditPlan extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'short_description',
        'plan_url',
        'credit_gateway',
        'cash_gateway',
        'position',
        'show_on_homepage_desktop',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'show_on_homepage_desktop' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHomepageDesktop($query)
    {
        return $query->where('show_on_homepage_desktop', true);
    }

    /**
     * طرح‌های قابل استفاده در جمع‌بندی نماینده:
     * فعال + حداقل یک درگاه (نقدی و/یا اعتباری) که در کاتالوگ روشن باشد.
     * طرح فقط‌نقدی (مثل دیبا) و فقط‌اعتباری و ترکیبی پذیرفته می‌شوند.
     *
     * @param  Builder<CreditPlan>  $query
     * @return Builder<CreditPlan>
     */
    public function scopeForRepresentativeCheckout(Builder $query): Builder
    {
        $catalog = app(PaymentGatewayCatalog::class);
        $creditNames = collect($catalog->credit())->pluck('name')->all();
        $cashNames = collect($catalog->cash())->pluck('name')->all();

        return $query
            ->active()
            ->where(function (Builder $outer) use ($creditNames, $cashNames): void {
                // فقط نقدی
                $outer->where(function (Builder $onlyCash) use ($cashNames): void {
                    $onlyCash
                        ->where(function (Builder $c): void {
                            $c->whereNull('credit_gateway')->orWhere('credit_gateway', '');
                        })
                        ->whereNotNull('cash_gateway')
                        ->where('cash_gateway', '!=', '')
                        ->when(
                            $cashNames !== [],
                            fn (Builder $q) => $q->whereIn('cash_gateway', $cashNames),
                            fn (Builder $q) => $q->whereRaw('1 = 0'),
                        );
                })
                // فقط اعتباری
                    ->orWhere(function (Builder $onlyCredit) use ($creditNames): void {
                        $onlyCredit
                            ->where(function (Builder $c): void {
                                $c->whereNull('cash_gateway')->orWhere('cash_gateway', '');
                            })
                            ->whereNotNull('credit_gateway')
                            ->where('credit_gateway', '!=', '')
                            ->when(
                                $creditNames !== [],
                                fn (Builder $q) => $q->whereIn('credit_gateway', $creditNames),
                                fn (Builder $q) => $q->whereRaw('1 = 0'),
                            );
                    })
                // ترکیبی: هر دو پر و هر دو فعال
                    ->orWhere(function (Builder $both) use ($creditNames, $cashNames): void {
                        $both
                            ->whereNotNull('credit_gateway')
                            ->where('credit_gateway', '!=', '')
                            ->whereNotNull('cash_gateway')
                            ->where('cash_gateway', '!=', '')
                            ->when(
                                $creditNames !== [],
                                fn (Builder $q) => $q->whereIn('credit_gateway', $creditNames),
                                fn (Builder $q) => $q->whereRaw('1 = 0'),
                            )
                            ->when(
                                $cashNames !== [],
                                fn (Builder $q) => $q->whereIn('cash_gateway', $cashNames),
                                fn (Builder $q) => $q->whereRaw('1 = 0'),
                            );
                    });
            })
            ->orderBy('position')
            ->orderBy('name');
    }

    public function hasCheckoutGateways(): bool
    {
        return $this->filledCashGateway() || $this->filledCreditGateway();
    }

    public function filledCreditGateway(): bool
    {
        return filled($this->credit_gateway);
    }

    public function filledCashGateway(): bool
    {
        return filled($this->cash_gateway);
    }

    /** هر دو درگاه برای پرداخت ترکیبی */
    public function supportsSplitCheckout(): bool
    {
        return $this->filledCreditGateway() && $this->filledCashGateway();
    }

    public function isCashOnlyCheckout(): bool
    {
        return $this->filledCashGateway() && ! $this->filledCreditGateway();
    }

    public function isCreditOnlyCheckout(): bool
    {
        return $this->filledCreditGateway() && ! $this->filledCashGateway();
    }
}
