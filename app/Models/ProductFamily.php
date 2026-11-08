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
        'show_in_mega',
        'position',
        'homepage_popup_enabled',
        'show_on_homepage_sidebar',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_mega' => 'boolean',
            'homepage_popup_enabled' => 'boolean',
            'show_on_homepage_sidebar' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function megaMenuLinks(): HasMany
    {
        return $this->hasMany(MegaMenuLink::class);
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
