<?php

namespace App\Models;

use App\Models\Concerns\HasAdminShopPreview;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MegaMenuLink extends Model
{
    use HasAdminShopPreview;

    protected $fillable = [
        'root_category_id',
        'product_family_id',
        'parent_id',
        'category_id',
        'label',
        'url',
        'position',
        'is_active',
        'admin_shop_preview',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'admin_shop_preview' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(ProductFamily::class, 'product_family_id');
    }

    public function rootCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'root_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MegaMenuLink::class, 'parent_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(MegaMenuLink::class, 'parent_id')->orderBy('position')->orderBy('id');
    }
}
