<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaReservationRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'representative_id',
        'sales_manager_id',
        'status',
        'note',
        'response_note',
        'handled_by_id',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'در انتظار بررسی',
            self::STATUS_APPROVED => 'تأیید و رزرو شد',
            self::STATUS_REJECTED => 'رد شد',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Requests whose representative has no assigned manager are visible to every sales manager.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isBankdar()) {
            return $query->where(fn (Builder $q) => $q
                ->where('sales_manager_id', $user->id)
                ->orWhereNull('sales_manager_id'));
        }

        if ($user->isSalesManager()) {
            $bankdarIds = User::query()
                ->where('is_bankdar', true)
                ->where('created_by_sales_manager_id', $user->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return $query->where(fn (Builder $q) => $q
                ->when($bankdarIds !== [], fn (Builder $q) => $q->whereIn('sales_manager_id', $bankdarIds))
                ->orWhereNull('sales_manager_id')
                ->orWhere('sales_manager_id', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function representative(): BelongsTo
    {
        return $this->belongsTo(User::class, 'representative_id');
    }

    public function salesManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_manager_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }
}
