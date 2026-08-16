<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Payment\PaymentService;
use App\Services\Sms\OrderSmsNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected OrderSmsNotifier $sms,
    ) {}

    public function callback(Request $request)
    {
        $payment = Payment::where('tracking_code', $request->query('payment'))->firstOrFail();

        try {
            $payment = $this->paymentService->verify(
                $payment,
                (string) $request->query('Authority', ''),
                (string) $request->query('Status', '')
            );
        } catch (\Throwable $e) {
            Log::error('Payment verify failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('account.orders.show', $payment->order_id)
                ->with('payment_status', Payment::STATUS_FAILED);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            $payment->loadMissing('user', 'order');
            $payment->user?->notify(new PaymentSuccessNotification($payment));
            if ($payment->order) {
                $this->sms->orderPaid($payment->order);
            }
        }

        return redirect()
            ->route('account.orders.show', $payment->order_id)
            ->with('payment_status', $payment->status);
    }
}
