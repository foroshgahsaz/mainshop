<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Payment\PaymentService;
use App\Services\Sms\OrderSmsNotifier;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected OrderSmsNotifier $sms,
    ) {}

    public function callback(Request $request)
    {
        $payment = Payment::where('tracking_code', $request->query('payment'))->firstOrFail();

        $payment = $this->paymentService->verify(
            $payment,
            (string) $request->query('Authority', ''),
            (string) $request->query('Status', '')
        );

        if ($payment->status === Payment::STATUS_SUCCESS) {
            $payment->user->notify(new PaymentSuccessNotification($payment));
            $this->sms->orderPaid($payment->order);
        }

        return redirect()
            ->route('account.orders.show', $payment->order_id)
            ->with('payment_status', $payment->status);
    }
}
