@extends('layouts.shop')

@section('title', 'نتیجه پرداخت — '.site_name())

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        @php
            $success = $payment->status === \App\Models\Payment::STATUS_SUCCESS;
            $canceled = $payment->status === \App\Models\Payment::STATUS_CANCELED;
            $order = $order ?? null;
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
                    @if ($payment->wasPaidByRepresentative())
                        بازگشت به پیش‌فاکتور
                    @else
                        مشاهده سفارش
                    @endif
                </a>
                @guest
                    <a href="{{ route('login', ['redirect' => $returnUrl]) }}" class="shop-btn-outline inline-block px-6 py-3 rounded-xl">
                        ورود به حساب کاربری
                    </a>
                @endguest
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/payment-result.css') }}?v={{ filemtime(public_path('shop/css/payment-result.css')) }}">
@endpush
