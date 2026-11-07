<?php

namespace App\Models;

use App\Models\Concerns\HasAdminShopPreview;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasAdminShopPreview;

    protected $fillable = [
        'slug',
        'title',
        'content',
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
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
