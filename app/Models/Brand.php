<?php

namespace App\Models;

use App\Models\Concerns\HasAdminShopPreview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasAdminShopPreview;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
        'admin_shop_preview',
        'position',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keyword',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'robots',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'admin_shop_preview' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productTemplates(): HasMany
    {
        return $this->hasMany(ProductTemplate::class);
    }
}
