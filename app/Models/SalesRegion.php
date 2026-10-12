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

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_manager_id');
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class)->orderBy('position');
    }
}
