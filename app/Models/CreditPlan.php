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
     * طرح‌های قابل استفاده در جمع‌بندی نماینده: فعال + هر دو درگاه تنظیم و روشن.
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
            ->whereNotNull('credit_gateway')
            ->whereNotNull('cash_gateway')
            ->where('credit_gateway', '!=', '')
            ->where('cash_gateway', '!=', '')
            ->when($creditNames !== [], fn (Builder $q) => $q->whereIn('credit_gateway', $creditNames), fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->when($cashNames !== [], fn (Builder $q) => $q->whereIn('cash_gateway', $cashNames), fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->orderBy('position')
            ->orderBy('name');
    }

    public function hasCheckoutGateways(): bool
    {
        return filled($this->credit_gateway) && filled($this->cash_gateway);
    }
}
