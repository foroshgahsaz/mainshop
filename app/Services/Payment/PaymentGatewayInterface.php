<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function initiate(Payment $payment, Order $order): string;

    public function verify(Payment $payment, string $authority, string $status): Payment;
}
