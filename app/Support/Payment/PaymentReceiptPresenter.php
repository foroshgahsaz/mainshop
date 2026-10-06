<?php

namespace App\Support\Payment;

use App\Models\Payment;
use App\Support\ShopLabels;

class PaymentReceiptPresenter
{
    /** @return array<string, mixed> */
    public static function for(Payment $payment): array
    {
        $payment->loadMissing(['order.user', 'order.representative', 'user', 'paidByRepresentative']);

        $order = $payment->order;

        return [
            'title' => 'رسید پرداخت موفق',
            'site_name' => site_name(),
            'gateway_label' => ShopLabels::paymentMethod($payment->gateway),
            'payment_tracking' => $payment->tracking_code,
            'gateway_reference' => $payment->gatewayReference(),
            'amount' => (int) $payment->amount,
            'paid_at' => $payment->paid_at?->shopJalali(),
            'order_tracking' => $order?->tracking_code,
            'order_amount' => $order ? (int) $order->final_amount : null,
            'customer_name' => $order?->user?->name,
            'customer_phone' => $order?->user?->phone,
            'representative_name' => $order?->representative?->name ?? $payment->paidByRepresentative?->name,
        ];
    }
}
