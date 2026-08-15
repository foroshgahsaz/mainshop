<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    public const TYPE_LINK = 'link';

    public const TYPE_MEGA_TRIGGER = 'mega_trigger';

    public const TYPE_MEGA_PROMO = 'mega_promo';

    public const TYPE_ACCORDION = 'accordion';

    public const LOCATION_DESKTOP = 'desktop';

    public const LOCATION_MOBILE = 'mobile';

    public const LOCATION_BOTH = 'both';

    protected $fillable = [
        'parent_id',
        'label',
        'item_type',
        'link_type',
        'link_value',
        'location',
        'mega_column',
        'position',
        'is_active',
        'open_in_new_tab',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'open_in_new_tab' => 'boolean',
            'mega_column' => 'integer',
            'position' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('position');
    }

    public function resolveUrl(): string
    {
        return match ($this->link_type) {
            'url' => $this->link_value ?: '#',
            'category' => ($category = Category::query()->find($this->link_value))
                ? route('categories.show', $category)
                : '#',
            'page' => $this->link_value
                ? route('pages.show', $this->link_value)
                : '#',
            'route' => ($this->link_value && \Illuminate\Support\Facades\Route::has($this->link_value))
                ? route($this->link_value)
                : '#',
            default => '#',
        };
    }

    public function showsOnDesktop(): bool
    {
        return in_array($this->location, [self::LOCATION_DESKTOP, self::LOCATION_BOTH], true);
    }

    public function showsOnMobile(): bool
    {
        return in_array($this->location, [self::LOCATION_MOBILE, self::LOCATION_BOTH], true);
    }
}
