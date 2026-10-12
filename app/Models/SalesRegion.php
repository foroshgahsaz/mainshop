<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesRegion extends Model
{
    protected $fillable = [
        'name',
        'sales_manager_id',
    ];

    protected static function booted(): void
    {
        static::deleting(function (SalesRegion $region): void {
            Province::query()->where('sales_region_id', $region->id)->update(['sales_region_id' => null]);
        });
    }

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_manager_id');
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class)->orderBy('position');
    }
}
