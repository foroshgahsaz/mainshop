<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFamilyHomepagePopupItem extends Model
{
    protected $fillable = [
        'product_family_id',
        'label',
        'url',
        'image',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function productFamily(): BelongsTo
    {
        return $this->belongsTo(ProductFamily::class);
    }
}
