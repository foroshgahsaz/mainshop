<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductTemplate extends Model
{
    protected $fillable = [
        'brand_id',
        'product_family_id',
        'name',
        'slug',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function productFamily(): BelongsTo
    {
        return $this->belongsTo(ProductFamily::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
