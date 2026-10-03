<?php

namespace App\Http\Controllers;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Payment;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Payment\PaymentAuditLogger;
use App\Services\Payment\PaymentAuditStep;
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
        protected PaymentAuditLogger $paymentAudit,
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
                ->to($this->paymentReturnUrl($model))
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

        $order = $payment->order()->first();

        $this->paymentAudit->step(
            $payment,
            PaymentAuditStep::CALLBACK_RECEIVED,
            'callback_received',
            'بازگشت کاربر از درگاه به سایت.',
            [
                'query' => $request->query(),
                'authority' => $authority,
                'status' => $status,
            ]
        );

        try {
            $payment = $this->paymentService->verify($payment, $authority, $status);
        } catch (\Throwable $e) {
            $this->paymentAudit->failure(
                $payment,
                PaymentAuditStep::VERIFY_RESULT,
                'callback_verify_exception',
                'خطا هنگام پردازش بازگشت از درگاه.',
                $e
            );

            Log::error('Payment verify failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            if ($order) {
                $this->sms->paymentFailed($order->fresh(['user']), $payment);
            }

            return redirect()
                ->to($this->paymentReturnUrl($payment))
                ->with('payment_status', Payment::STATUS_FAILED);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            $payment->loadMissing('user', 'order');
            $payment->user?->notify(new PaymentSuccessNotification($payment));

            if ($order) {
                $this->sms->orderPaid($order, $payment);

                if (! $order->isPaid()) {
                    $this->sms->paymentPartialRemaining($order, (int) $order->remainingAmount());
                }
            }
        } elseif ($order && in_array($payment->status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELED], true)) {
            $this->sms->paymentFailed($order->fresh(['user']), $payment);
        }

        $flash = ['payment_status' => $payment->status];

        if ($payment->status === Payment::STATUS_SUCCESS && $order && ! $order->isPaid()) {
            $flash['payment_remaining'] = $order->remainingAmount();
        }

        return redirect()
            ->to($this->paymentReturnUrl($payment))
            ->with($flash);
    }

    protected function paymentReturnUrl(Payment $payment): string
    {
        $payment->loadMissing('order');

        if ($payment->wasPaidByRepresentative() && $payment->order?->isRepresentativeOrder()) {
            return DraftOrderResource::getUrl('view', ['record' => $payment->order_id], panel: 'representative');
        }

        return route('account.orders.show', $payment->order_id);
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
