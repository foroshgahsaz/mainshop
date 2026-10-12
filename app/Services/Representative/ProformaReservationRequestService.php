<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\ProformaReservationRequest;
use App\Models\User;
use App\Services\Cart\StockService;
use App\Services\Order\OrderActivityLogger;
use App\Services\Sms\OrderSmsNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProformaReservationRequestService
{
    public function __construct(
        protected StockService $stockService,
        protected OrderActivityLogger $orderLog,
        protected OrderSmsNotifier $sms,
    ) {}

    public function canRequest(Order $order, User $representative): bool
    {
        return $order->isProforma()
            && $order->representative_id === $representative->id
            && ! $order->hasSuccessfulPayment()
            && ! $order->hasActiveStockReservation()
            && ! $this->hasPendingRequest($order);
    }

    public function hasPendingRequest(Order $order): bool
    {
        return ProformaReservationRequest::query()
            ->where('order_id', $order->id)
            ->pending()
            ->exists();
    }

    public function request(Order $order, User $representative, ?string $note = null): ProformaReservationRequest
    {
        return DB::transaction(function () use ($order, $representative, $note) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->representative_id !== $representative->id || ! $locked->isProforma()) {
                throw ValidationException::withMessages(['order' => 'این پیش‌فاکتور قابل درخواست رزرو نیست.']);
            }

            if ($locked->hasSuccessfulPayment()) {
                throw ValidationException::withMessages(['order' => 'این پیش‌فاکتور پرداخت شده است.']);
            }

            if ($locked->hasActiveStockReservation()) {
                throw ValidationException::withMessages(['order' => 'رزرو موجودی این پیش‌فاکتور هنوز فعال است.']);
            }

            if ($this->hasPendingRequest($locked)) {
                throw ValidationException::withMessages(['order' => 'یک درخواست رزرو مجدد در انتظار بررسی است.']);
            }

            $note = trim((string) $note);

            $request = ProformaReservationRequest::query()->create([
                'order_id' => $locked->id,
                'representative_id' => $representative->id,
                'sales_manager_id' => $representative->representativeProfile?->sales_manager_id,
                'status' => ProformaReservationRequest::STATUS_PENDING,
                'note' => $note !== '' ? $note : null,
            ]);

            $this->orderLog->system(
                $locked,
                'نماینده درخواست رزرو مجدد موجودی ثبت کرد.',
                'proforma_reservation_requested',
                ['request_id' => $request->id]
            );

            return $request;
        });
    }

    public function approve(ProformaReservationRequest $request, User $actor, ?string $responseNote = null): ProformaReservationRequest
    {
        $this->assertCanHandle($request, $actor);

        $order = DB::transaction(function () use ($request, $actor, $responseNote) {
            /** @var ProformaReservationRequest $lockedRequest */
            $lockedRequest = ProformaReservationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRequest->isPending()) {
                throw ValidationException::withMessages(['request' => 'این درخواست قبلاً بررسی شده است.']);
            }

            /** @var Order $order */
            $order = Order::query()->whereKey($lockedRequest->order_id)->lockForUpdate()->firstOrFail();

            if (! $order->isProforma() || $order->hasSuccessfulPayment()) {
                throw ValidationException::withMessages(['request' => 'پیش‌فاکتور دیگر قابل رزرو مجدد نیست.']);
            }

            if (! $order->stock_reserved) {
                $this->stockService->assertOrderAvailable($order);
                $this->stockService->decrementOrderItems($order);
            }

            $minutes = (int) config('shop.representative.proforma_reservation_ttl_minutes', 1440);
            $expiresAt = now()->addMinutes(max(1, $minutes));

            $order->update([
                'stock_reserved' => true,
                'stock_reserved_until' => $expiresAt,
            ]);

            $lockedRequest->update([
                'status' => ProformaReservationRequest::STATUS_APPROVED,
                'response_note' => $this->cleanNote($responseNote),
                'handled_by_id' => $actor->id,
                'handled_at' => now(),
            ]);

            $this->orderLog->byUser(
                $order->fresh(),
                $actor,
                'درخواست رزرو مجدد تأیید شد؛ موجودی تا '.$expiresAt->shopJalali().' رزرو شد.',
                'private',
                'proforma_reservation_rereserved',
                ['request_id' => $lockedRequest->id]
            );

            return $order->fresh(['user']);
        });

        $this->sms->proformaReservationExtended($order);

        return $request->fresh();
    }

    public function reject(ProformaReservationRequest $request, User $actor, ?string $responseNote = null): ProformaReservationRequest
    {
        $this->assertCanHandle($request, $actor);

        return DB::transaction(function () use ($request, $actor, $responseNote) {
            /** @var ProformaReservationRequest $locked */
            $locked = ProformaReservationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['request' => 'این درخواست قبلاً بررسی شده است.']);
            }

            $locked->update([
                'status' => ProformaReservationRequest::STATUS_REJECTED,
                'response_note' => $this->cleanNote($responseNote),
                'handled_by_id' => $actor->id,
                'handled_at' => now(),
            ]);

            $this->orderLog->byUser(
                $locked->order,
                $actor,
                'درخواست رزرو مجدد رد شد.'.($locked->response_note ? ' دلیل: '.$locked->response_note : ''),
                'private',
                'proforma_reservation_request_rejected',
                ['request_id' => $locked->id]
            );

            return $locked->fresh();
        });
    }

    public function pendingCountFor(User $user): int
    {
        return ProformaReservationRequest::query()->visibleTo($user)->pending()->count();
    }

    protected function assertCanHandle(ProformaReservationRequest $request, User $actor): void
    {
        $visible = ProformaReservationRequest::query()
            ->whereKey($request->id)
            ->visibleTo($actor)
            ->exists();

        if (! $visible) {
            throw ValidationException::withMessages(['request' => 'اجازه بررسی این درخواست را ندارید.']);
        }
    }

    protected function cleanNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note !== '' ? $note : null;
    }
}
