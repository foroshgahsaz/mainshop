<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderNote;
use App\Models\User;
use App\Support\ShopLabels;

class OrderActivityLogger
{
    public function system(Order $order, string $message, ?string $event = null, array $metadata = []): OrderNote
    {
        return $order->notes()->create([
            'user_id' => null,
            'type' => OrderNote::TYPE_SYSTEM,
            'event' => $event,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }

    public function byUser(Order $order, User $user, string $message, string $type = OrderNote::TYPE_PRIVATE, ?string $event = null, array $metadata = []): OrderNote
    {
        return $order->notes()->create([
            'user_id' => $user->id,
            'type' => $type,
            'event' => $event,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }

    public function orderCreated(Order $order, User $customer): OrderNote
    {
        return $this->system(
            $order,
            sprintf(
                'سفارش توسط %s ثبت شد. روش پرداخت: %s. مبلغ: %s',
                $customer->name ?: $customer->phone,
                ShopLabels::paymentMethod($order->payment_method),
                ShopLabels::formatMoney($order->final_amount)
            ),
            'order_created',
            [
                'customer_id' => $customer->id,
                'payment_method' => $order->payment_method,
                'final_amount' => $order->final_amount,
            ]
        );
    }

    public function statusChanged(Order $order, string $from, string $to, ?User $actor = null): OrderNote
    {
        $message = sprintf(
            'وضعیت سفارش از «%s» به «%s» تغییر کرد.',
            ShopLabels::orderStatus($from),
            ShopLabels::orderStatus($to)
        );

        if ($actor) {
            $message .= ' (توسط '.$actor->name.')';
        }

        $payload = [
            'type' => OrderNote::TYPE_SYSTEM,
            'event' => 'status_changed',
            'message' => $message,
            'metadata' => ['from' => $from, 'to' => $to],
        ];

        if ($actor) {
            $payload['user_id'] = $actor->id;
        }

        return $order->notes()->create($payload);
    }

    public function trackingUpdated(Order $order, ?string $from, ?string $to, ?User $actor = null): OrderNote
    {
        $message = $from
            ? sprintf('کد رهگیری پست از «%s» به «%s» تغییر کرد.', $from, $to ?: '—')
            : sprintf('کد رهگیری پست ثبت شد: %s', $to);

        if ($actor) {
            $message .= ' (توسط '.$actor->name.')';
        }

        return $order->notes()->create([
            'user_id' => $actor?->id,
            'type' => OrderNote::TYPE_SYSTEM,
            'event' => 'tracking_updated',
            'message' => $message,
            'metadata' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function paymentLinked(Order $order, string $paymentTracking, string $status, ?string $extra = null): OrderNote
    {
        $message = sprintf(
            'پرداخت %s — وضعیت: %s%s',
            $paymentTracking,
            ShopLabels::paymentStatus($status),
            $extra ? '. '.$extra : ''
        );

        return $this->system($order, $message, 'payment_event', [
            'payment_tracking' => $paymentTracking,
            'payment_status' => $status,
        ]);
    }

    public function customerCanceled(Order $order, User $customer): OrderNote
    {
        return $this->byUser(
            $order,
            $customer,
            'مشتری سفارش را لغو کرد.',
            OrderNote::TYPE_SYSTEM,
            'customer_canceled'
        );
    }
}
