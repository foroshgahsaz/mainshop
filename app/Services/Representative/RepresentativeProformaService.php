<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\User;
use App\Services\Cart\StockService;
use App\Services\Order\OrderActivityLogger;
use App\Services\Sms\OrderSmsNotifier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RepresentativeProformaService
{
    public function __construct(
        protected StockService $stockService,
        protected OrderActivityLogger $orderLog,
        protected OrderSmsNotifier $sms,
    ) {}

    public function reserveStockForProforma(Order $order, User $representative): Order
    {
        $this->assertReservationCapacity($representative);

        return DB::transaction(function () use ($order, $representative) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->representative_id !== $representative->id || ! $locked->isDraft()) {
                throw ValidationException::withMessages([
                    'order' => 'این پیش‌فاکتور قابل ثبت نیست.',
                ]);
            }

            if ($locked->stock_reserved) {
                return $locked;
            }

            $this->stockService->assertOrderAvailable($locked);
            $this->stockService->decrementOrderItems($locked);

            $minutes = (int) config('shop.representative.proforma_reservation_ttl_minutes', 1440);
            $expiresAt = now()->addMinutes(max(1, $minutes));

            $locked->update([
                'stock_reserved' => true,
                'stock_reserved_until' => $expiresAt,
            ]);

            $this->orderLog->system(
                $locked->fresh(),
                'موجودی پیش‌فاکتور رزرو شد تا '.$expiresAt->format('Y/m/d H:i').'.',
                'proforma_stock_reserved'
            );

            return $locked->fresh();
        });
    }

    public function assertReservationCapacity(User $representative): void
    {
        $profile = $representative->representativeProfile;
        $cap = (int) ($profile?->max_active_reservations ?? 3);

        if ($cap <= 0) {
            return;
        }

        $active = $this->countActiveReservations($representative);

        if ($active >= $cap) {
            throw ValidationException::withMessages([
                'order' => "حداکثر {$cap} پیش‌فاکتور با رزرو فعال مجاز است. یکی را پرداخت یا منتظر انقضای رزرو بمانید.",
            ]);
        }
    }

    public function countActiveReservations(User $representative): int
    {
        return $this->activeReservedProformasQuery($representative)->count();
    }

    /**
     * @return Collection<int, Order>
     */
    public function activeReservedProformas(User $representative): Collection
    {
        return $this->activeReservedProformasQuery($representative)
            ->with(['user:id,name,phone', 'freightCarrier'])
            ->orderByDesc('stock_reserved_until')
            ->orderByDesc('id')
            ->get();
    }

    protected function activeReservedProformasQuery(User $representative)
    {
        return Order::query()
            ->where('representative_id', $representative->id)
            ->where('status', Order::STATUS_PROFORMA)
            ->where('stock_reserved', true)
            ->where(function ($query) {
                $query->whereNull('stock_reserved_until')
                    ->orWhere('stock_reserved_until', '>', now());
            });
    }

    public function extendReservation(Order $order, int $extraMinutes, ?User $actor = null): Order
    {
        if ($extraMinutes < 1) {
            throw new RuntimeException('مدت تمدید باید حداقل یک دقیقه باشد.');
        }

        if (! $order->isProforma() || ! $order->stock_reserved) {
            throw new RuntimeException('فقط پیش‌فاکتور با رزرو فعال قابل تمدید است.');
        }

        $base = $order->stock_reserved_until && $order->stock_reserved_until->isFuture()
            ? $order->stock_reserved_until
            : now();

        $newUntil = $base->copy()->addMinutes($extraMinutes);

        $order->update(['stock_reserved_until' => $newUntil]);

        $message = 'مهلت رزرو موجودی تا '.$newUntil->format('Y/m/d H:i').' تمدید شد.';
        if ($actor?->isAdmin()) {
            $this->orderLog->byUser($order->fresh(), $actor, $message, 'private');
        } else {
            $this->orderLog->system($order->fresh(), $message, 'proforma_reservation_extended');
        }

        $order = $order->fresh(['user']);

        $this->sms->proformaReservationExtended($order);

        return $order;
    }
}
