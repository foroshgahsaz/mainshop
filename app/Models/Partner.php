<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'link',
        'position',
        'show_on_homepage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'show_on_homepage' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHomepage($query)
    {
        return $query->where('show_on_homepage', true);
    }
}
