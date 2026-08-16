<?php

namespace App\Services\Order;

use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderCanceledNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderShippedNotification;
use App\Services\Cart\StockService;
use App\Services\Sms\OrderSmsNotifier;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected StockService $stockService,
        protected OrderActivityLogger $orderLog,
        protected OrderSmsNotifier $sms,
    ) {}

    public function cancel(Order $order, ?User $actor = null): Order
    {
        return $this->finalizeCancel($order, $actor);
    }

    public function expireUnpaid(Order $order): Order
    {
        $order = $this->finalizeCancel($order, null, systemExpire: true);

        $this->orderLog->system(
            $order,
            'سفارش به‌دلیل عدم پرداخت در مهلت مقرر لغو شد و موجودی آزاد گردید.',
            'auto_canceled'
        );

        return $order;
    }

    public function markShipped(Order $order, ?string $trackingCode = null, ?User $actor = null): Order
    {
        $previousStatus = $order->status;
        $previousTracking = $order->shipping_tracking_code;

        $order->update([
            'status' => Order::STATUS_SHIPPED,
            'shipping_tracking_code' => $trackingCode ?? $order->shipping_tracking_code,
            'shipped_at' => now(),
        ]);

        $order = $order->fresh(['user']);

        if ($previousStatus !== Order::STATUS_SHIPPED) {
            $this->orderLog->statusChanged($order, $previousStatus, Order::STATUS_SHIPPED, $actor);
            $order->user?->notify(new OrderShippedNotification($order));
            $this->sms->orderShipped($order);
        }

        if ($trackingCode && $trackingCode !== $previousTracking) {
            $this->orderLog->trackingUpdated($order, $previousTracking, $trackingCode, $actor);
        }

        return $order;
    }

    public function markDelivered(Order $order, ?User $actor = null): Order
    {
        $previousStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        $order = $order->fresh(['user']);

        if ($previousStatus !== Order::STATUS_DELIVERED) {
            $this->orderLog->statusChanged($order, $previousStatus, Order::STATUS_DELIVERED, $actor);
            $order->user?->notify(new OrderDeliveredNotification($order));
            $this->sms->orderDelivered($order);
        }

        return $order;
    }

    public function updateStatus(Order $order, string $status, ?User $actor = null, ?string $privateNote = null): Order
    {
        $previousStatus = $order->status;

        if ($previousStatus === $status) {
            return $order;
        }

        if ($status === Order::STATUS_CANCELED) {
            return $this->cancel($order, $actor);
        }

        $updates = ['status' => $status];

        if ($status === Order::STATUS_SHIPPED && ! $order->shipped_at) {
            $updates['shipped_at'] = now();
        }

        if ($status === Order::STATUS_DELIVERED && ! $order->delivered_at) {
            $updates['delivered_at'] = now();
        }

        $order->update($updates);
        $order = $order->fresh(['user']);

        $this->orderLog->statusChanged($order, $previousStatus, $status, $actor);

        if ($privateNote && $actor) {
            $this->orderLog->byUser($order, $actor, $privateNote);
        }

        match ($status) {
            Order::STATUS_SHIPPED => $order->user?->notify(new OrderShippedNotification($order)),
            Order::STATUS_DELIVERED => $order->user?->notify(new OrderDeliveredNotification($order)),
            default => null,
        };

        match ($status) {
            Order::STATUS_SHIPPED => $this->sms->orderShipped($order),
            Order::STATUS_DELIVERED => $this->sms->orderDelivered($order),
            default => null,
        };

        return $order;
    }

    public function updateTracking(Order $order, ?string $trackingCode, ?User $actor = null): Order
    {
        $previous = $order->shipping_tracking_code;

        if ($previous === $trackingCode) {
            return $order;
        }

        $order->update(['shipping_tracking_code' => $trackingCode]);
        $this->orderLog->trackingUpdated($order->fresh(), $previous, $trackingCode, $actor);

        return $order->fresh();
    }

    protected function finalizeCancel(Order $order, ?User $actor, bool $systemExpire = false): Order
    {
        return DB::transaction(function () use ($order, $actor, $systemExpire) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $allowed = $systemExpire || ! $actor?->isAdmin()
                ? $order->canBeCanceledByCustomer()
                : $order->canBeCanceledByAdmin();

            if (! $allowed) {
                throw new \RuntimeException('این سفارش قابل لغو نیست.');
            }

            $previousStatus = $order->status;
            $wasPaid = $order->isPaid();

            if ($order->stock_reserved) {
                $this->stockService->restoreOrderItems($order);
                $order->stock_reserved = false;
            }

            $order->status = Order::STATUS_CANCELED;
            $order->save();

            if (! $wasPaid) {
                CouponUsage::query()->where('order_id', $order->id)->delete();
                $order->payments()
                    ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_FAILED])
                    ->update(['status' => Payment::STATUS_CANCELED]);
            } elseif ($actor?->isAdmin()) {
                $this->orderLog->system(
                    $order,
                    'سفارش پرداخت‌شده لغو شد. استرداد وجه در درگاه باید به‌صورت دستی انجام شود.',
                    'manual_refund_required'
                );
            }

            if ($actor && $actor->id === $order->user_id) {
                $this->orderLog->customerCanceled($order->fresh(), $actor);
            } else {
                $this->orderLog->statusChanged($order->fresh(), $previousStatus, Order::STATUS_CANCELED, $actor);
            }

            $order = $order->fresh(['user']);
            $order->user?->notify(new OrderCanceledNotification($order));
            $this->sms->orderCanceled($order);

            return $order;
        });
    }
}
