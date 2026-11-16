@extends('layouts.shop')

@section('title', 'تأیید پرداخت داریک | '.site_name())

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('body_class', 'account-page darik-otp-page')

@section('content')
@php
    $orderTracking = $order?->tracking_code;
    $amountLabel = function_exists('shop_money')
        ? shop_money($payment->amount)
        : number_format((int) $payment->amount).' تومان';
@endphp

<x-account-layout title="تأیید پرداخت داریک" active="orders">
    @if (session('error'))
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="shop-card p-5 mb-4 darik-otp-card">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <p class="text-xs text-gray-400 mb-1">پرداخت اعتباری از کیف پول داریک</p>
                <h2 class="font-bold text-navy text-lg">رمز خرید پیامک‌شده را وارد کنید</h2>
            </div>
            <span class="text-xs bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full">در انتظار OTP</span>
        </div>

        <dl class="grid gap-2 text-sm mb-5 border-b border-gray-100 pb-4">
            @if ($orderTracking)
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">کد سفارش</dt>
                    <dd class="font-bold text-navy" dir="ltr">{{ $orderTracking }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500">کد پرداخت</dt>
                <dd class="font-medium" dir="ltr">{{ $payment->tracking_code }}</dd>
            </div>
            <div class="flex justify-between gap-3 items-center">
                <dt class="text-gray-500">مبلغ</dt>
                <dd class="text-lg font-black text-brand-green">{{ $amountLabel }}</dd>
            </div>
        </dl>

        <p class="text-sm text-gray-600 leading-7 mb-4">
            رمز ۵ رقمی به موبایل ثبت‌شده در کیف پول داریک ارسال شده است.
            پس از تأیید، اعتبار کیف پول مصرف و سفارش تکمیل می‌شود.
        </p>

        <form method="post" action="{{ $confirmUrl }}" class="darik-otp-form">
            @csrf
            <label for="darik-otp" class="block text-sm font-bold text-navy mb-2">رمز خرید (OTP)</label>
            <input
                id="darik-otp"
                name="otp"
                type="text"
                inputmode="numeric"
                pattern="\d{5}"
                maxlength="5"
                autocomplete="one-time-code"
                required
                autofocus
                class="darik-otp-form__input"
                placeholder="•••••"
                value="{{ old('otp') }}"
            >
            <button type="submit" class="shop-btn-gold darik-otp-form__submit">
                تأیید و پرداخت
            </button>
        </form>

        <div class="darik-otp-actions">
            <form method="post" action="{{ $resendUrl }}">
                @csrf
                <button type="submit" class="shop-btn-outline text-sm">ارسال مجدد رمز</button>
            </form>
            <form method="post" action="{{ $cancelUrl }}">
                @csrf
                <button type="submit" class="darik-otp-actions__cancel text-sm">انصراف از پرداخت</button>
            </form>
            <a href="{{ route('account.orders') }}" class="darik-otp-actions__link text-sm">بازگشت به سفارش‌ها</a>
        </div>
    </div>
</x-account-layout>
@endsection
