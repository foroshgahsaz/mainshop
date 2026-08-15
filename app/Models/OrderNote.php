<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    public const TYPE_SYSTEM = 'system';

    public const TYPE_PRIVATE = 'private';

    public const TYPE_CUSTOMER = 'customer';

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'event',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isSystem(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }
}
