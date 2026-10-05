@php
    $success = $payment->status === \App\Models\Payment::STATUS_SUCCESS;
    $canceled = $payment->status === \App\Models\Payment::STATUS_CANCELED;
    $order = $order ?? null;
    $isRepresentative = $payment->wasPaidByRepresentative();
@endphp

<div class="shop-card p-6 md:p-8 payment-result-card {{ $success ? 'payment-result-card--success' : ($canceled ? 'payment-result-card--canceled' : 'payment-result-card--failed') }}">
    <div class="payment-result-card__icon" aria-hidden="true">
        @if ($success)
            ✓
        @elseif ($canceled)
            !
        @else
            ×
        @endif
    </div>

    <h1 class="payment-result-card__title">
        @if ($success)
            پرداخت با موفقیت انجام شد
        @elseif ($canceled)
            پرداخت لغو شد
        @else
            پرداخت ناموفق بود
        @endif
    </h1>

    <p class="payment-result-card__lead">
        @if ($success && $order && ! $order->isPaid())
            مبلغ پرداخت ثبت شد. برای تکمیل سفارش، مانده را نیز پرداخت کنید.
        @elseif ($success)
            از خرید شما سپاسگزاریم. جزئیات پرداخت و سفارش در ادامه آمده است.
        @elseif ($canceled)
            پرداخت در درگاه تکمیل نشد. در صورت نیاز می‌توانید دوباره تلاش کنید.
        @else
            تراکنش تأیید نشد. در صورت کسر وجه، با پشتیبانی تماس بگیرید.
        @endif
    </p>

    <dl class="payment-result-card__details">
        <div>
            <dt>درگاه</dt>
            <dd>{{ $gatewayLabel }}</dd>
        </div>
        <div>
            <dt>کد پیگیری پرداخت</dt>
            <dd dir="ltr">{{ $payment->tracking_code }}</dd>
        </div>
        @if ($payment->gatewayReference())
            <div>
                <dt>شناسه مرجع درگاه</dt>
                <dd dir="ltr" class="break-all">{{ $payment->gatewayReference() }}</dd>
            </div>
        @endif
        <div>
            <dt>مبلغ این پرداخت</dt>
            <dd>{{ number_format($payment->amount) }} تومان</dd>
        </div>
        @if ($payment->paid_at)
            <div>
                <dt>زمان ثبت</dt>
                <dd>{{ $payment->paid_at->timezone('Asia/Tehran')->format('Y/m/d H:i') }}</dd>
            </div>
        @endif
        @if ($order)
            <div>
                <dt>کد سفارش</dt>
                <dd dir="ltr">{{ $order->tracking_code }}</dd>
            </div>
            <div>
                <dt>مبلغ سفارش</dt>
                <dd>{{ number_format($order->final_amount) }} تومان</dd>
            </div>
            @if ($success && $order->remainingAmount() > 0)
                <div class="payment-result-card__remaining">
                    <dt>مانده سفارش</dt>
                    <dd>{{ number_format($order->remainingAmount()) }} تومان</dd>
                </div>
            @endif
        @endif
    </dl>

    <div class="payment-result-card__actions">
        <a href="{{ $returnUrl }}" class="shop-btn-primary inline-block px-6 py-3 rounded-xl">
            @if ($isRepresentative)
                بازگشت به پیش‌فاکتور
            @else
                مشاهده سفارش
            @endif
        </a>
        @if ($success && ! empty($receiptPdfUrl))
            <a href="{{ $receiptPdfUrl }}" class="shop-btn-outline inline-block px-6 py-3 rounded-xl" target="_blank" rel="noopener">
                دانلود PDF رسید پرداخت
            </a>
        @endif
        @guest
            @if ($isRepresentative)
                <a href="{{ route('filament.representative.auth.login') }}" class="shop-btn-outline inline-block px-6 py-3 rounded-xl">
                    ورود به پنل نمایندگی
                </a>
            @else
                <a href="{{ route('login', ['redirect' => $returnUrl]) }}" class="shop-btn-outline inline-block px-6 py-3 rounded-xl">
                    ورود به حساب کاربری
                </a>
            @endif
        @endguest
    </div>
</div>
