<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\PaymentReturnUrl;
use App\Support\ShopLabels;
use Illuminate\View\View;

class PaymentResultController extends Controller
{
    public function show(Payment $payment): View
    {
        $payment->loadMissing(['order.items', 'order.user', 'user']);

        return view('payments.result', [
            'payment' => $payment,
            'order' => $payment->order,
            'gatewayLabel' => ShopLabels::paymentMethod($payment->gateway),
            'returnUrl' => PaymentReturnUrl::for($payment),
            'receiptPdfUrl' => null,
        ]);
    }
}
