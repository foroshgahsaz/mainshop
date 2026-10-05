<?php

namespace App\Http\Controllers\Representative;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\PaymentReturnUrl;
use App\Support\ShopLabels;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class RepresentativePaymentResultController extends Controller
{
    public function show(Payment $payment): View
    {
        $payment->loadMissing(['order.items', 'order.user', 'user', 'paidByRepresentative']);

        abort_unless(
            $payment->wasPaidByRepresentative() && $payment->order?->isRepresentativeOrder(),
            404
        );

        return view('payments.representative-result', [
            'payment' => $payment,
            'order' => $payment->order,
            'gatewayLabel' => ShopLabels::paymentMethod($payment->gateway),
            'returnUrl' => PaymentReturnUrl::for($payment),
            'receiptPdfUrl' => $this->receiptPdfUrl($payment),
        ]);
    }

    public static function receiptPdfUrl(Payment $payment): string
    {
        return URL::temporarySignedRoute('representative.payment.receipt.pdf', now()->addHours(6), [
            'payment' => $payment->tracking_code,
        ]);
    }
}
