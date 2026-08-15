<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'usage_limit',
        'usage_per_user',
        'minimum_order_amount',
        'starts_at',
        'expires_at',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'max_discount' => 'integer',
            'usage_limit' => 'integer',
            'usage_per_user' => 'integer',
            'minimum_order_amount' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValidFor(?User $user, int $orderTotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->minimum_order_amount && $orderTotal < $this->minimum_order_amount) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usages()->count() >= $this->usage_limit) {
            return false;
        }

        if ($user && $this->usage_per_user !== null) {
            $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $this->usage_per_user) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(int $orderTotal): int
    {
        if ($this->type === self::TYPE_FIXED) {
            return min($this->value, $orderTotal);
        }

        $discount = (int) floor($orderTotal * ($this->value / 100));

        if ($this->max_discount !== null) {
            $discount = min($discount, $this->max_discount);
        }

        return min($discount, $orderTotal);
    }
}
