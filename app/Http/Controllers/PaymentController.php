<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Notifications\PaymentSuccessNotification;
use App\Services\Payment\DarikGateway;
use App\Services\Payment\PaymentAuditLogger;
use App\Services\Payment\PaymentAuditStep;
use App\Services\Payment\PaymentService;
use App\Services\Payment\TaraGateway;
use App\Services\Settings\SettingsService;
use App\Services\Sms\OrderSmsNotifier;
use App\Support\PaymentReturnUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

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

    public function bajetCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function digipayCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function snapppayCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function parsianCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function sadadCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function nopayCallback(Request $request)
    {
        return $this->handleCallback($request);
    }

    public function darikOtp(string $tracking)
    {
        $payment = Payment::query()
            ->where('tracking_code', $tracking)
            ->where('gateway', 'darik')
            ->firstOrFail();

        $this->assertDarikPaymentAccess($payment);

        if ($payment->status !== Payment::STATUS_PENDING) {
            return redirect()->to($this->paymentReturnUrl($payment))
                ->with('error', 'این پرداخت داریک دیگر در انتظار رمز نیست.');
        }

        if (! data_get($payment->raw_response, 'darik.transaction_token')) {
            return redirect()->to($this->paymentReturnUrl($payment))
                ->with('error', 'اطلاعات تراکنش داریک یافت نشد.');
        }

        return response()
            ->view('payments.darik-otp', [
                'payment' => $payment,
                'order' => $payment->order,
                'resendUrl' => route('payment.darik.resend', ['tracking' => $payment->tracking_code]),
                'confirmUrl' => route('payment.darik.confirm', ['tracking' => $payment->tracking_code]),
                'cancelUrl' => route('payment.darik.cancel', ['tracking' => $payment->tracking_code]),
            ])
            ->header('Cache-Control', 'no-store');
    }

    public function darikConfirm(Request $request, string $tracking)
    {
        $payment = Payment::query()
            ->where('tracking_code', $tracking)
            ->where('gateway', 'darik')
            ->firstOrFail();

        $this->assertDarikPaymentAccess($payment);

        $validated = $request->validate([
            'otp' => ['required', 'regex:/^\d{5}$/'],
        ], [
            'otp.required' => 'رمز خرید را وارد کنید.',
            'otp.regex' => 'رمز خرید باید ۵ رقم باشد.',
        ]);

        $otp = preg_replace('/\D+/', '', (string) $validated['otp']) ?? '';

        return $this->processDarikVerification($payment, $otp);
    }

    public function darikCancel(string $tracking)
    {
        $payment = Payment::query()
            ->where('tracking_code', $tracking)
            ->where('gateway', 'darik')
            ->firstOrFail();

        $this->assertDarikPaymentAccess($payment);

        return $this->processDarikVerification($payment, '', 'cancel');
    }

    public function darikResendOtp(string $tracking, DarikGateway $gateway)
    {
        $payment = Payment::query()
            ->where('tracking_code', $tracking)
            ->where('gateway', 'darik')
            ->firstOrFail();

        $this->assertDarikPaymentAccess($payment);

        try {
            $gateway->resendOtp($payment);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'رمز خرید مجدداً ارسال شد.');
    }

    protected function processDarikVerification(Payment $payment, string $otp, string $status = 'confirm')
    {
        $order = $payment->order()->first();

        $this->paymentAudit->step(
            $payment,
            PaymentAuditStep::CALLBACK_RECEIVED,
            'darik_otp_submit',
            'ارسال رمز خرید داریک از سایت.',
            ['status' => $status]
        );

        try {
            $payment = $this->paymentService->verify($payment, $otp, $status);
        } catch (\Throwable $e) {
            $this->paymentAudit->failure(
                $payment,
                PaymentAuditStep::VERIFY_RESULT,
                'darik_verify_exception',
                'خطا هنگام تأیید پرداخت داریک.',
                $e
            );

            Log::error('Darik verify failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            if ($order) {
                try {
                    $this->sms->paymentFailed($order->fresh(['user']), $payment);
                } catch (\Throwable $smsError) {
                    Log::error('Darik payment-failed sms failed', [
                        'payment_id' => $payment->id,
                        'error' => $smsError->getMessage(),
                    ]);
                }
            }

            return redirect()->route('payment.darik.otp', ['tracking' => $payment->tracking_code])
                ->with('error', $e->getMessage());
        }

        if ($status === 'confirm' && $payment->status === Payment::STATUS_FAILED) {
            $errorCode = strtoupper((string) data_get($payment->raw_response, 'errorCode', ''));
            $message = (string) data_get($payment->raw_response, 'message', 'رمز خرید نامعتبر است.');

            if ($errorCode === 'INVALID_OTP' || $errorCode === 'INVALID_INPUT') {
                $payment->update(['status' => Payment::STATUS_PENDING]);

                return redirect()->route('payment.darik.otp', ['tracking' => $payment->tracking_code])
                    ->with('error', $message !== '' ? $message : 'رمز خرید نامعتبر است. دوباره تلاش کنید.');
            }
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            try {
                $payment->loadMissing('user', 'order');
                $payment->user?->notify(new PaymentSuccessNotification($payment));

                if ($order) {
                    $order = $order->fresh();
                    $this->sms->orderPaid($order, $payment);

                    if (! $order->isPaid()) {
                        $this->sms->paymentPartialRemaining($order, (int) $order->remainingAmount());
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Darik post-success notification failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($order && in_array($payment->status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELED], true)) {
            try {
                $this->sms->paymentFailed($order->fresh(['user']), $payment);
            } catch (\Throwable $e) {
                Log::error('Darik payment-failed sms failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->redirectToPaymentResult($payment->fresh());
    }

    protected function assertDarikPaymentAccess(Payment $payment): void
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $allowed = $user->id === $payment->user_id
            || $user->id === $payment->paid_by_representative_id
            || $user->is_admin;

        if (! $allowed) {
            abort(403);
        }
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
        try {
            return $this->handleCallbackCore($request);
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Payment callback unhandled exception', [
                'path' => $request->path(),
                'query' => $request->query(),
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('home')
                ->with('error', 'خطا در پردازش بازگشت از درگاه. اگر مبلغ از حساب شما کسر شده، با پشتیبانی چینی‌بازار تماس بگیرید.');
        }
    }

    protected function handleCallbackCore(Request $request)
    {
        $payment = $this->resolvePayment($request);

        if ($payment->gateway === 'bajet') {
            $authority = (string) ($request->input('id') ?: $payment->transaction_id);
            $status = (string) $request->input('status', '');
        } elseif ($payment->gateway === 'digipay') {
            $callbackPayload = array_merge($request->query(), $request->post());
            $authority = (string) (
                $request->input('trackingCode')
                ?: $request->query('trackingCode')
                ?: $payment->transaction_id
            );
            $status = (string) (
                $request->input('result')
                ?: $request->query('result')
                ?: ''
            );

            $mergedRaw = array_merge(
                is_array($payment->raw_response) ? $payment->raw_response : [],
                [
                    'digipay_callback' => $callbackPayload,
                ]
            );
            if (isset($callbackPayload['type']) && $callbackPayload['type'] !== '') {
                $mergedRaw['digipay_callback_type'] = $callbackPayload['type'];
            }
            if (isset($callbackPayload['providerId']) && $callbackPayload['providerId'] !== '') {
                $mergedRaw['digipay_callback_provider_id'] = $callbackPayload['providerId'];
            }

            $attributes = ['raw_response' => $mergedRaw];
            if ($authority !== '') {
                $attributes['transaction_id'] = $authority;
            }

            $payment->update($attributes);
            $payment = $payment->fresh();
        } elseif ($payment->gateway === 'snapppay') {
            $authority = (string) ($payment->transaction_id ?: data_get($payment->raw_response, 'snapppay_payment_token', ''));
            $status = (string) ($request->input('state') ?: $request->input('State') ?: '');
            $payment->update([
                'raw_response' => array_merge(
                    is_array($payment->raw_response) ? $payment->raw_response : [],
                    ['snapppay_callback' => $request->except(['payment'])]
                ),
            ]);
            $payment = $payment->fresh();
        } elseif ($payment->gateway === 'parsian') {
            $authority = (string) ($request->input('Token') ?: $request->input('token') ?: $payment->transaction_id);
            $status = (string) $request->input('status', '');
            $payment->update([
                'raw_response' => array_merge(
                    is_array($payment->raw_response) ? $payment->raw_response : [],
                    ['callback' => $request->except(['payment'])]
                ),
            ]);
            $payment = $payment->fresh();
        } elseif ($payment->gateway === 'sadad') {
            $authority = (string) ($request->input('token') ?: $request->input('Token') ?: $payment->transaction_id);
            $status = (string) $request->input('ResCode', '');
            $payment->update([
                'raw_response' => array_merge(
                    is_array($payment->raw_response) ? $payment->raw_response : [],
                    ['callback' => $request->except(['payment'])]
                ),
            ]);
            $payment = $payment->fresh();
        } elseif (in_array($payment->gateway, ['nopay', 'toopi'], true)) {
            $authority = (string) (
                $request->input('tv')
                ?: $request->input('t')
                ?: $request->input('token')
                ?: $request->input('Token')
                ?: $payment->transaction_id
            );
            $status = (string) ($request->input('status') ?: $request->input('Status') ?: '');
            $payment->update([
                'raw_response' => array_merge(
                    is_array($payment->raw_response) ? $payment->raw_response : [],
                    ['callback' => $request->except(['payment'])]
                ),
            ]);
            $payment = $payment->fresh();
        } else {
            $authority = (string) ($request->input('Authority') ?: $request->input('token') ?: $payment->transaction_id);
            $status = (string) ($request->input('Status') ?: $request->input('result') ?: '');
        }

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
            ],
            $request->all()
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
                try {
                    $this->sms->paymentFailed($order->fresh(['user']), $payment);
                } catch (\Throwable $smsError) {
                    Log::error('Payment callback payment-failed sms failed after verify exception', [
                        'payment_id' => $payment->id,
                        'error' => $smsError->getMessage(),
                    ]);
                }
            }

            $payment = $payment->fresh();

            return $this->redirectToPaymentResult($payment);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            try {
                $payment->loadMissing('user', 'order');
                $payment->user?->notify(new PaymentSuccessNotification($payment));

                if ($order) {
                    $order = $order->fresh();
                    $this->sms->orderPaid($order, $payment);

                    if (! $order->isPaid()) {
                        $this->sms->paymentPartialRemaining($order, (int) $order->remainingAmount());
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Payment callback post-success notification failed', [
                    'payment_id' => $payment->id,
                    'tracking_code' => $payment->tracking_code,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($order && in_array($payment->status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELED], true)) {
            try {
                $this->sms->paymentFailed($order->fresh(['user']), $payment);
            } catch (\Throwable $e) {
                Log::error('Payment callback payment-failed sms failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->redirectToPaymentResult($payment->fresh());
    }

    protected function redirectToPaymentResult(Payment $payment)
    {
        $flash = ['payment_status' => $payment->status];
        $order = $payment->order;

        if ($payment->status === Payment::STATUS_SUCCESS && $order && ! $order->isPaid()) {
            $flash['payment_remaining'] = $order->remainingAmount();
        }

        $resultRoute = 'payment.result';
        if ($payment->wasPaidByRepresentative() && $payment->order?->isRepresentativeOrder()) {
            $resultRoute = 'representative.payment.result';
        }

        return redirect()
            ->to(URL::temporarySignedRoute($resultRoute, now()->addHours(6), [
                'payment' => $payment->tracking_code,
            ]))
            ->with($flash);
    }

    protected function paymentReturnUrl(Payment $payment): string
    {
        return PaymentReturnUrl::for($payment);
    }

    protected function resolvePayment(Request $request): Payment
    {
        $tracking = (string) $request->input('payment', $request->query('payment', ''));

        if (str_contains($tracking, '?tv=')) {
            [$tracking, $rest] = explode('?tv=', $tracking, 2);
            $embeddedTv = trim(explode('&', $rest)[0]);
            if ($embeddedTv !== '') {
                $byEmbeddedTv = Payment::query()
                    ->where('transaction_id', $embeddedTv)
                    ->whereIn('gateway', ['nopay', 'toopi'])
                    ->first();
                if ($byEmbeddedTv) {
                    return $byEmbeddedTv;
                }
            }
        }

        $tracking = trim($tracking);
        if ($tracking !== '') {
            $byTracking = Payment::query()->where('tracking_code', $tracking)->first();
            if ($byTracking) {
                return $byTracking;
            }
        }

        $tv = trim((string) ($request->input('tv', $request->query('tv', ''))));
        if ($tv !== '') {
            $byTv = Payment::query()
                ->where('transaction_id', $tv)
                ->whereIn('gateway', ['nopay', 'toopi'])
                ->first();
            if ($byTv) {
                return $byTv;
            }
        }

        $orderId = (string) ($request->input('OrderId') ?: $request->input('orderId', ''));
        if ($orderId !== '' && ctype_digit($orderId)) {
            $byPecOrderId = Payment::query()
                ->whereKey((int) $orderId)
                ->whereIn('gateway', ['parsian', 'sadad'])
                ->first();
            if ($byPecOrderId) {
                return $byPecOrderId;
            }
        }
        if ($orderId !== '') {
            $byOrderId = Payment::query()->where('tracking_code', $orderId)->first();
            if ($byOrderId) {
                return $byOrderId;
            }
        }

        $token = (string) ($request->input('Token') ?: $request->input('token', ''));
        if ($token !== '') {
            $byToken = Payment::query()->where('transaction_id', $token)->first();
            if ($byToken) {
                return $byToken;
            }
        }

        $providerId = trim((string) ($request->input('providerId') ?: $request->query('providerId', '')));
        if ($providerId !== '') {
            if (str_contains($providerId, '___')) {
                $trackingFromProvider = explode('___', $providerId, 2)[0];
                $byProviderPrefix = Payment::query()
                    ->where('tracking_code', $trackingFromProvider)
                    ->where('gateway', 'digipay')
                    ->first();
                if ($byProviderPrefix) {
                    return $byProviderPrefix;
                }
            }

            $byDigipayProvider = Payment::query()
                ->where('gateway', 'digipay')
                ->where('raw_response->digipay_provider_id', $providerId)
                ->first();
            if ($byDigipayProvider) {
                return $byDigipayProvider;
            }
        }

        $digipayTracking = trim((string) (
            $request->input('trackingCode')
            ?: $request->query('trackingCode', '')
        ));
        if ($digipayTracking !== '') {
            $byDigipayTracking = Payment::query()
                ->where('transaction_id', $digipayTracking)
                ->where('gateway', 'digipay')
                ->first();
            if ($byDigipayTracking) {
                return $byDigipayTracking;
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
