<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'province_id',
        'city_id',
        'receiver_name',
        'receiver_phone',
        'province',
        'city',
        'address',
        'postal_code',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provinceModel(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function cityModel(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'address_id');
    }
}
