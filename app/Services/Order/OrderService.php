<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCanceledNotification;
use App\Notifications\OrderDeliveredNotification;
use App\Notifications\OrderShippedNotification;
use App\Services\Cart\StockService;
use App\Services\Sms\OrderSmsNotifier;

class OrderService
{
    public function __construct(
        protected StockService $stockService,
        protected OrderActivityLogger $orderLog,
        protected OrderSmsNotifier $sms,
    ) {}

    public function cancel(Order $order, ?User $actor = null): Order
    {
        if (! $order->canBeCanceled()) {
            throw new \RuntimeException('این سفارش قابل لغو نیست.');
        }

        $previousStatus = $order->status;

        if ($order->stockWasDeducted()) {
            $this->stockService->restoreOrderItems($order);
        }

        $order->update(['status' => Order::STATUS_CANCELED]);

        if ($actor && $actor->id === $order->user_id) {
            $this->orderLog->customerCanceled($order->fresh(), $actor);
        } else {
            $this->orderLog->statusChanged($order->fresh(), $previousStatus, Order::STATUS_CANCELED, $actor);
        }

        $order = $order->fresh(['user']);
        $order->user?->notify(new OrderCanceledNotification($order));
        $this->sms->orderCanceled($order);

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

        $shouldRestoreStock = $status === Order::STATUS_CANCELED && $order->stockWasDeducted();

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
            Order::STATUS_CANCELED => $order->user?->notify(new OrderCanceledNotification($order)),
            default => null,
        };

        match ($status) {
            Order::STATUS_SHIPPED => $this->sms->orderShipped($order),
            Order::STATUS_DELIVERED => $this->sms->orderDelivered($order),
            Order::STATUS_CANCELED => $this->sms->orderCanceled($order),
            default => null,
        };

        if ($shouldRestoreStock) {
            $this->stockService->restoreOrderItems($order);
        }

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
}
