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

    public const STATUS_WAREHOUSE_PROCESSING = 'warehouse_processing';

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
        'proforma_planned_credit_amount',
        'proforma_cash_gateway',
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
            'proforma_planned_credit_amount' => 'integer',
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

    public static function directShopRepresentativeId(): ?int
    {
        $id = (int) config('shop.checkout.direct_representative_id', 0);

        return $id > 0 ? $id : null;
    }

    public function isDirectShopRepresentativeAttribution(): bool
    {
        $directId = self::directShopRepresentativeId();

        return $directId !== null && (int) $this->representative_id === $directId;
    }

    public function isRepresentativeOrder(): bool
    {
        return $this->representative_id !== null && ! $this->isDirectShopRepresentativeAttribution();
    }

    public function representativeLabel(): string
    {
        if ($this->representative_id !== null) {
            if ($this->relationLoaded('representative') && $this->representative) {
                return (string) $this->representative->name;
            }

            return (string) ($this->representative()->value('name') ?? '—');
        }

        $directId = self::directShopRepresentativeId();
        if ($directId !== null) {
            return (string) (User::query()->whereKey($directId)->value('name') ?? '—');
        }

        return '—';
    }

    public function catalogTemplateLabel(): string
    {
        if (array_key_exists('catalog_template_label', $this->attributes)) {
            return (string) $this->attributes['catalog_template_label'];
        }

        $filters = $this->catalog_filters ?? [];

        if (! empty($filters['template_name']) && is_string($filters['template_name'])) {
            return $filters['template_name'];
        }

        $templateId = $filters['template_id'] ?? null;

        if ($templateId === null || $templateId === '') {
            return '—';
        }

        return ProductTemplate::query()->whereKey((int) $templateId)->value('name') ?? '—';
    }

    /** @param  iterable<int, self>  $orders */
    public static function attachCatalogTemplateLabels(iterable $orders): void
    {
        $lookupIds = [];

        foreach ($orders as $order) {
            if (! $order instanceof self) {
                continue;
            }

            $filters = $order->catalog_filters ?? [];

            if (! empty($filters['template_name']) && is_string($filters['template_name'])) {
                $order->attributes['catalog_template_label'] = $filters['template_name'];

                continue;
            }

            $templateId = $filters['template_id'] ?? null;

            if ($templateId === null || $templateId === '') {
                $order->attributes['catalog_template_label'] = '—';

                continue;
            }

            $lookupIds[(int) $templateId][] = $order;
        }

        if ($lookupIds === []) {
            return;
        }

        $names = ProductTemplate::query()
            ->whereIn('id', array_keys($lookupIds))
            ->pluck('name', 'id');

        foreach ($lookupIds as $templateId => $ordersForId) {
            $label = (string) ($names[$templateId] ?? '—');

            foreach ($ordersForId as $order) {
                $order->attributes['catalog_template_label'] = $label;
            }
        }
    }

    public function reservationRequests(): HasMany
    {
        return $this->hasMany(ProformaReservationRequest::class);
    }

    public function latestReservationRequest(): HasOne
    {
        return $this->hasOne(ProformaReservationRequest::class)->latestOfMany();
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
        return app(\App\Services\Representative\RepresentativeProformaSplitPayment::class)
            ->isSettlementComplete($this);
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
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_WAREHOUSE_PROCESSING,
        ], true);
    }

    public function canAdvanceToWarehouseProcessing(): bool
    {
        if (! $this->isPaid()) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
        ], true);
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

    public function isPendingShopOnlinePayment(): bool
    {
        if ($this->representative_id !== null && ! $this->isDirectShopRepresentativeAttribution()) {
            return false;
        }

        $method = (string) ($this->payment_method ?? '');

        if ($method === '' || $method === 'cod') {
            return false;
        }

        if ($method === 'online') {
            return true;
        }

        return app(PaymentGatewayCatalog::class)->isEnabled($method);
    }

    public function paymentMethodLabel(): string
    {
        return \App\Support\ShopLabels::orderPaymentMethodLabel($this);
    }

    public function canInitiateOnlinePayment(): bool
    {
        if ($this->remainingAmount() <= 0 || ! $this->hasActiveStockReservation()) {
            return false;
        }

        if ($this->status === self::STATUS_PENDING && $this->isPendingShopOnlinePayment()) {
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

        if (! $this->hasActiveStockReservation()) {
            return false;
        }

        $split = app(\App\Services\Representative\RepresentativeProformaSplitPayment::class);

        if ($split->isSettlementComplete($this)) {
            return false;
        }

        if ($split->usesSplitPlan($this)) {
            $catalog = app(PaymentGatewayCatalog::class);

            if ($split->remainingCreditAmount($this) > 0 && ! $catalog->isEnabled((string) $this->payment_method)) {
                return false;
            }

            if ($split->remainingCashAmount($this) > 0 && ! $catalog->isEnabled($split->cashGateway($this))) {
                return false;
            }

            return true;
        }

        if ($this->remainingAmount() <= 0) {
            return false;
        }

        return app(PaymentGatewayCatalog::class)->isEnabled((string) $this->payment_method);
    }
}
