<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpMessage extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'user_id',
        'user_name',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        $name = trim((string) ($this->user_name ?: $this->user?->name ?: ''));

        return $name !== '' ? $name : '—';
    }
}
