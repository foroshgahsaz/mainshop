<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPlan extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'short_description',
        'plan_url',
        'position',
        'show_on_homepage_desktop',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'show_on_homepage_desktop' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHomepageDesktop($query)
    {
        return $query->where('show_on_homepage_desktop', true);
    }
}
