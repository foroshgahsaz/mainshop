<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FreightCarrier extends Model
{
    protected $fillable = [
        'name',
        'carrier_number',
        'province_id',
        'city_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function displayLabel(): string
    {
        $province = $this->province?->name;
        $city = $this->city?->name;
        $location = trim(collect([$province, $city])->filter()->implode(' — '));

        $parts = [$this->name, 'شماره '.$this->carrier_number];
        if ($location !== '') {
            $parts[] = $location;
        }

        return implode(' · ', $parts);
    }
}
