<?php

namespace App\Models;

use App\Services\Payment\PaymentGatewayCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const STATUS_DRAFT = 'draft';

    /** پیش‌فاکتور ثبت‌شده توسط نماینده — فقط مشاهده برای نماینده */
    public const STATUS_PROFORMA = 'proforma';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'user_id',
        'representative_id',
        'address_id',
        'coupon_id',
        'shipping_method_id',
        'freight_carrier_id',
        'total_amount',
        'final_amount',
        'shipping_amount',
        'discount_amount',
        'payment_method',
        'status',
        'stock_reserved',
        'stock_reserved_until',
        'tracking_code',
        'shipping_tracking_code',
        'note',
        'catalog_filters',
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
            'stock_reserved_until' => 'datetime',
            'catalog_filters' => 'array',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isProforma(): bool
    {
        return $this->status === self::STATUS_PROFORMA;
    }

    public function isRepresentativeEditable(): bool
    {
        return $this->isDraft();
    }

    public function isRepresentativeOrder(): bool
    {
        return $this->representative_id !== null;
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'representative_id');
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

    public function freightCarrier(): BelongsTo
    {
        return $this->belongsTo(FreightCarrier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(OrderInvoiceLine::class)->orderBy('sort_order')->orderBy('id');
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
        return $this->canInitiateOnlinePayment();
    }

    public function hasActiveStockReservation(): bool
    {
        if (! $this->stock_reserved) {
            return false;
        }

        if ($this->stock_reserved_until === null) {
            return true;
        }

        return $this->stock_reserved_until->isFuture();
    }

    public function isReservationExpired(): bool
    {
        return $this->stock_reserved
            && $this->stock_reserved_until !== null
            && $this->stock_reserved_until->isPast();
    }

    public function canInitiateOnlinePayment(): bool
    {
        if ($this->remainingAmount() <= 0 || ! $this->hasActiveStockReservation()) {
            return false;
        }

        if ($this->status === self::STATUS_PENDING && $this->payment_method === 'online') {
            return true;
        }

        if ($this->isProforma() && $this->isRepresentativeOrder()) {
            return app(PaymentGatewayCatalog::class)->isEnabled((string) $this->payment_method);
        }

        return false;
    }

    public function proformaPaymentGateway(): ?string
    {
        if ($this->isProforma() && $this->isRepresentativeOrder()) {
            return (string) $this->payment_method;
        }

        return null;
    }

    public function canRepresentativePayProforma(): bool
    {
        if (! $this->isProforma() || ! $this->isRepresentativeOrder()) {
            return false;
        }

        if ($this->remainingAmount() <= 0 || ! $this->hasActiveStockReservation()) {
            return false;
        }

        return app(PaymentGatewayCatalog::class)->isEnabled((string) $this->payment_method);
    }
}
