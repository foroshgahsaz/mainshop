<?php

namespace App\Support;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Payment;

class PaymentReturnUrl
{
    public static function for(Payment $payment): string
    {
        $payment->loadMissing('order');
        $order = $payment->order;

        if ($payment->wasPaidByRepresentative() && $order?->isRepresentativeOrder()) {
            return DraftOrderResource::getUrl('view', ['record' => $order->getKey()], panel: 'representative');
        }

        return route('account.orders.show', $payment->order_id);
    }
}
