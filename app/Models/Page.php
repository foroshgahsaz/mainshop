<?php

namespace App\Models;

use App\Models\Concerns\HasAdminShopPreview;
use App\Support\OrderedProductIds;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasAdminShopPreview;

    public const LAYOUT_CONTENT = 'content';

    public const LAYOUT_PRODUCT_GRID = 'product_grid';

    protected $fillable = [
        'slug',
        'title',
        'layout',
        'content',
        'product_ids',
        'is_active',
        'admin_shop_preview',
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
            'product_ids' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isProductGrid(): bool
    {
        return $this->layout === self::LAYOUT_PRODUCT_GRID;
    }

    /** @return list<int> */
    public function curatedProductIds(): array
    {
        return is_array($this->product_ids) ? array_values($this->product_ids) : [];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if ($page->layout !== self::LAYOUT_PRODUCT_GRID) {
                $page->product_ids = null;

                return;
            }

            $page->product_ids = OrderedProductIds::parse($page->product_ids ?? []);
        });
    }
}
