<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoiceLine extends Model
{
    public const KIND_FEE = 'fee';

    public const KIND_ORDER_DISCOUNT = 'order_discount';

    protected $fillable = [
        'order_id',
        'kind',
        'title',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isFee(): bool
    {
        return $this->kind === self::KIND_FEE;
    }

    public function isOrderDiscount(): bool
    {
        return $this->kind === self::KIND_ORDER_DISCOUNT;
    }
}
