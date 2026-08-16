<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Payment\PaymentService;
use App\Services\Payment\TaraGateway;
use App\Services\Settings\SettingsService;
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
        return $this->handleCallback($request);
    }

    public function taraCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function taraRedirect(string $tracking, TaraGateway $gateway, SettingsService $settings)
    {
        $model = Payment::query()
            ->where('tracking_code', $tracking)
            ->where('gateway', 'tara')
            ->firstOrFail();

        if ($model->status !== Payment::STATUS_PENDING || ! $model->transaction_id) {
            return redirect()
                ->route('account.orders.show', $model->order_id)
                ->with('error', 'این درخواست پرداخت تارا معتبر نیست.');
        }

        $config = $settings->tara();

        return response()
            ->view('payments.tara-redirect', [
                'action' => $gateway->purchaseAction($config),
                'username' => $config['username'],
                'token' => $model->transaction_id,
            ])
            ->header('Cache-Control', 'no-store');
    }

    protected function handleCallback(Request $request)
    {
        $payment = $this->resolvePayment($request);

        $authority = (string) ($request->input('Authority') ?: $request->input('token') ?: $payment->transaction_id);
        $status = (string) ($request->input('Status') ?: $request->input('result') ?: '');

        try {
            $payment = $this->paymentService->verify($payment, $authority, $status);
        } catch (\Throwable $e) {
            Log::error('Payment verify failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('account.orders.show', $payment->order_id)
                ->with('payment_status', Payment::STATUS_FAILED);
        }

        $order = $payment->order()->first();

        if ($payment->status === Payment::STATUS_SUCCESS) {
            $payment->loadMissing('user', 'order');
            $payment->user?->notify(new PaymentSuccessNotification($payment));

            if ($order?->isPaid()) {
                $this->sms->orderPaid($order);
            }
        }

        $flash = ['payment_status' => $payment->status];

        if ($payment->status === Payment::STATUS_SUCCESS && $order && ! $order->isPaid()) {
            $flash['payment_remaining'] = $order->remainingAmount();
        }

        return redirect()
            ->route('account.orders.show', $payment->order_id)
            ->with($flash);
    }

    protected function resolvePayment(Request $request): Payment
    {
        $tracking = (string) $request->input('payment', $request->query('payment', ''));

        if ($tracking !== '') {
            $byTracking = Payment::query()->where('tracking_code', $tracking)->first();
            if ($byTracking) {
                return $byTracking;
            }
        }

        $orderId = (string) $request->input('orderId', '');
        if ($orderId !== '') {
            $byOrderId = Payment::query()->where('tracking_code', $orderId)->first();
            if ($byOrderId) {
                return $byOrderId;
            }
        }

        $token = (string) $request->input('token', '');
        if ($token !== '') {
            $byToken = Payment::query()->where('transaction_id', $token)->first();
            if ($byToken) {
                return $byToken;
            }
        }

        $additional = (string) $request->input('additionalData', '');
        if ($additional !== '') {
            $byAdditional = Payment::query()->where('tracking_code', $additional)->first();
            if ($byAdditional) {
                return $byAdditional;
            }
        }

        abort(404);
    }
}
