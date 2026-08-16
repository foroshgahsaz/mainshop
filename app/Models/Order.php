<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'user_id',
        'address_id',
        'coupon_id',
        'shipping_method_id',
        'total_amount',
        'final_amount',
        'shipping_amount',
        'discount_amount',
        'payment_method',
        'status',
        'stock_reserved',
        'tracking_code',
        'shipping_tracking_code',
        'note',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'final_amount' => 'integer',
            'shipping_amount' => 'integer',
            'discount_amount' => 'integer',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'stock_reserved' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function couponUsage(): HasOne
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->latest();
    }

    public function paidAmount(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) $this->payments
                ->where('status', Payment::STATUS_SUCCESS)
                ->sum('amount');
        }

        return (int) $this->payments()->where('status', Payment::STATUS_SUCCESS)->sum('amount');
    }

    public function remainingAmount(): int
    {
        return max(0, (int) $this->final_amount - $this->paidAmount());
    }

    public function hasSuccessfulPayment(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains('status', Payment::STATUS_SUCCESS);
        }

        return $this->payments()->where('status', Payment::STATUS_SUCCESS)->exists();
    }

    public function isPaid(): bool
    {
        return $this->paidAmount() >= (int) $this->final_amount;
    }

    public function canBeCanceled(): bool
    {
        return $this->canBeCanceledByCustomer();
    }

    public function canBeCanceledByCustomer(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->hasSuccessfulPayment();
    }

    public function canBeCanceledByAdmin(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }

    public function stockWasDeducted(): bool
    {
        return (bool) $this->stock_reserved;
    }

    public function canPayAgain(): bool
    {
        return $this->payment_method === 'online'
            && $this->status === self::STATUS_PENDING
            && $this->remainingAmount() > 0;
    }
}
