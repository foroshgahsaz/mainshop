@extends('layouts.shop')

@section('title', 'راهنمای خرید اقساطی | '.$siteName)

@php
    $seoDescription = 'راهنمای گام‌به‌گام خرید اعتباری و اقساطی از '.$siteName.'؛ انتخاب کالا، checkout و طرح‌های تارا، دیجی‌پی، نوپی، باجت‌پی و سایر درگاه‌ها.';
    $canonicalUrl = route('plans.how-to-buy');
@endphp

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
        'title' => 'راهنمای خرید اقساطی از '.$siteName,
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'image' => $seoImage ?? null,
        'type' => 'website',
    ])" />
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/how-to-buy.css') }}?v=2026-11-07">
@endpush

@section('body_class', 'how-to-buy-page')

@section('content')
<div class="how-to-buy">
    <header class="how-to-buy__hero">
        <div class="htb-wrap">
            <span class="how-to-buy__eyebrow">{{ $siteName }}</span>
            <h1>چطور از {{ $siteName }} <span style="color:#c41e3a">قسطی</span> بخرم؟</h1>
            <p>
                مثل یک فروشگاه آنلاین معمولی کالا را انتخاب کنید؛ در مرحلهٔ پرداخت، یکی از طرح‌های اعتباری فعال را بزنید.
                همهٔ مراحل زیر در وب‌سایت {{ $siteName }} انجام می‌شود — نیازی به واسطهٔ جدا نیست.
            </p>
            <div class="how-to-buy__hero-actions">
                <a class="how-to-buy__btn how-to-buy__btn--primary" href="{{ route('products.index') }}">شروع خرید ←</a>
                <a class="how-to-buy__btn how-to-buy__btn--ghost" href="#payment-plans">مشاهده طرح‌های پرداخت</a>
            </div>
        </div>
    </header>

    <section class="how-to-buy__steps">
        <div class="htb-wrap">
            <div class="how-to-buy__section-title">
                <h2>مراحل خرید از {{ $siteName }}</h2>
                <p>از انتخاب محصول تا تحویل — خلاصهٔ مسیر مشتری</p>
            </div>
            <div class="how-to-buy__step-grid">
                <article class="how-to-buy__step">
                    <span class="how-to-buy__step-num" aria-hidden="true">۱</span>
                    <h3>ثبت‌نام / ورود</h3>
                    <p>با شماره موبایل در {{ $siteName }} وارد شوید یا حساب بسازید.</p>
                </article>
                <article class="how-to-buy__step">
                    <span class="how-to-buy__step-num" aria-hidden="true">۲</span>
                    <h3>انتخاب کالا</h3>
                    <p>محصول را به سبد اضافه کنید و به تسویه‌حساب بروید.</p>
                </article>
                <article class="how-to-buy__step">
                    <span class="how-to-buy__step-num" aria-hidden="true">۳</span>
                    <h3>پرداخت اعتباری</h3>
                    <p>در checkout یکی از درگاه‌های اعتباری فعال (مثلاً تارا، دیجی‌پی، نوپی) را انتخاب کنید.</p>
                </article>
                <article class="how-to-buy__step">
                    <span class="how-to-buy__step-num" aria-hidden="true">۴</span>
                    <h3>پیگیری سفارش</h3>
                    <p>پس از پرداخت موفق، وضعیت ارسال را از حساب کاربری دنبال کنید.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="how-to-buy__plans" id="payment-plans">
        <div class="htb-wrap">
            <div class="how-to-buy__section-title">
                <h2>طرح‌های پرداخت در {{ $siteName }}</h2>
                <p>در زمان checkout فقط درگاه‌های فعال برای سبد شما نمایش داده می‌شوند.</p>
            </div>

            @foreach ($planRows as $row)
                <div class="how-to-buy__plan-row {{ count($row['plans']) === 1 ? 'how-to-buy__plan-row--single' : '' }}">
                    <div class="how-to-buy__plan-row-head">
                        <h3>{{ $row['title'] }}</h3>
                        <p>{{ $row['lead'] }}</p>
                    </div>
                    <div class="how-to-buy__plan-grid">
                        @foreach ($row['plans'] as $plan)
                            <article class="how-to-buy__plan-card">
                                <div class="how-to-buy__plan-card-top">
                                    <div class="how-to-buy__plan-icon" aria-hidden="true">
                                        @if (! empty($plan['icon']))
                                            <img src="{{ $plan['icon'] }}" alt="">
                                        @else
                                            <span class="how-to-buy__plan-icon-fallback">{{ mb_substr($plan['label'], 0, 1) }}</span>
                                        @endif
                                    </div>
                                    <h4>{{ $plan['label'] }}</h4>
                                </div>
                                <p>{{ $plan['description'] }}</p>
                                @if (! empty($plan['cta']))
                                    <a class="how-to-buy__plan-cta" href="{{ $plan['cta'] }}">شروع خرید ←</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <p class="how-to-buy__note">
                لیست بالا راهنمای طرح‌هایی است که {{ $siteName }} پشتیبانی می‌کند یا در checkout ارائه می‌شود.
                شرایط اعتبار، تعداد اقساط و سقف مبلغ طبق قوانین هر سرویس (بانک یا کیف پول) تعیین می‌شود.
            </p>
        </div>
    </section>

    <section class="how-to-buy__cta-band">
        <div class="htb-wrap">
            <h2>همین الان از {{ $siteName }} خرید کنید</h2>
            <p>لیست کامل طرح‌ها را در صفحهٔ طرح‌های اعتباری هم ببینید.</p>
            <a class="how-to-buy__btn how-to-buy__btn--ghost" href="{{ route('plans.index') }}">مشاهدهٔ صفحهٔ طرح‌ها</a>
        </div>
    </section>
</div>
@endsection
