<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'position',
        'sales_region_id',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function salesRegion(): BelongsTo
    {
        return $this->belongsTo(SalesRegion::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class)->orderBy('position');
    }
}
