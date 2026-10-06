<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

final class TaraInvoiceItemBuilder
{
    /**
     * @param  array<string, mixed>  $config  Output of SettingsService::tara()
     * @return array<string, mixed>
     */
    public function build(Order $order, Payment $payment, array $config, int $gatewayAmount): array
    {
        return [
            'name' => 'سفارش '.$order->tracking_code,
            'code' => $payment->tracking_code,
            'count' => 1,
            'unit' => 5,
            'fee' => $gatewayAmount,
            'group' => (string) $config['default_group'],
            'groupTitle' => (string) $config['default_group_title'],
            'data' => $order->tracking_code,
        ];
    }
}
