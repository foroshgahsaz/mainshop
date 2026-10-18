<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductFamily extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'is_active',
        'position',
        'homepage_popup_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'homepage_popup_enabled' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function homepagePopupItems(): HasMany
    {
        return $this->hasMany(ProductFamilyHomepagePopupItem::class)->orderBy('position')->orderBy('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ProductTemplate::class);
    }

    public function plants(): HasMany
    {
        return $this->hasMany(ProductPlant::class);
    }
}
