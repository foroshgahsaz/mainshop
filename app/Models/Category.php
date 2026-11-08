<?php

namespace App\Models;

use App\Models\Concerns\HasAdminShopPreview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasAdminShopPreview;

    protected $fillable = [
        'parent_id',
        'product_family_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'admin_shop_preview',
        'position',
        'show_in_mega',
        'mega_column',
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
            'show_in_mega' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function productFamily(): BelongsTo
    {
        return $this->belongsTo(ProductFamily::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if ($category->parent_id && ! $category->product_family_id) {
                $parentFamilyId = static::query()
                    ->whereKey($category->parent_id)
                    ->value('product_family_id');

                if ($parentFamilyId) {
                    $category->product_family_id = $parentFamilyId;
                }
            }
        });
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKey(): string
    {
        return app(\App\Services\Shop\CategoryPathResolver::class)->pathFor($this);
    }
}
