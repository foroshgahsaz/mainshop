@extends('layouts.shop')

@section('title', 'خرید با کیف پول داریک از چینی بازار | پرداخت اعتباری')

@php
    $seoTitle = 'خرید با کیف پول داریک از چینی بازار | پرداخت اعتباری';
    $seoDescription = 'راهنمای خرید از چینی بازار با درگاه داریک؛ شناسه کیف پول ۱۶رقمی، تأیید با رمز پیامکی و ثبت سفارش در فروشگاه.';
    $seoKeywords = 'خرید اعتباری داریک, داریک چینی بازار, کیف پول داریک, پرداخت اعتباری, checkout داریک';
    $canonicalUrl = route('plans.darik');
@endphp

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
        'title' => $seoTitle,
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'image' => $seoImage ?? null,
        'type' => 'website',
    ])" />
@endsection

@push('meta')
    <meta name="keywords" content="{{ $seoKeywords }}">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/plans-leasing.css') }}?v=2026-11-16-darik">
@endpush

@section('body_class', 'plan-leasing-page')

@section('content')
<div class="plan-leasing">
    <section class="hero">
        <div class="wrap hero-grid">
            <div class="hero-copy">
                <span class="eyebrow"><i></i> کیف پول اعتباری</span>
                <h1>خرید از چینی بازار<br><span>با اعتبار کیف پول داریک</span></h1>
                <p>
                    کالا را در فروشگاه انتخاب کنید و در checkout درگاه داریک را بزنید.
                    شناسه ۱۶رقمی کیف پول (شروع با <span dir="ltr">5892</span>) را وارد کنید و پرداخت را با رمز پنج‌رقمی پیامک‌شده تأیید کنید.
                </p>
                <div class="hero-actions">
                    <a class="btn" href="{{ route('products.index') }}">شروع خرید از چینی بازار <span>←</span></a>
                    <a class="btn secondary" href="#wallet">آشنایی با کیف پول داریک</a>
                </div>
                <div class="trustline">
                    <div class="avatars"><span>✓</span><span>↗</span><span>★</span></div>
                    <span>انتخاب کالا در چینی بازار، پرداخت از موجودی کیف پول داریک</span>
                </div>
            </div>

            <div class="visual" aria-label="نمای کیف پول داریک">
                <div class="visual-ground" aria-hidden="true"></div>
                <div class="halo"></div>
                <div class="float-card fc1">
                    <span class="float-icon">✦</span>
                    <strong>پرداخت اعتباری</strong>
                    موجودی کیف پول، یک‌جا در checkout
                </div>
                <div class="phone">
                    <div class="notch"></div>
                    <div class="phone-top"><span>۹:۴۱</span><span>●●● ▰</span></div>
                    <div class="phone-top" style="margin-top:16px">
                        <span>سلام، خوش اومدی</span>
                        <span class="mini-logo">داریک</span>
                    </div>
                    <div class="balance">
                        <small>شناسه کیف پول</small>
                        <strong dir="ltr" style="font-size:1.05rem;letter-spacing:.04em">5892 •••• ••••</strong>
                        <div class="balance-foot">
                            <span>۱۶ رقم</span>
                            <span>کارت یا اپ داریک</span>
                        </div>
                    </div>
                    <div class="phone-title">مراحل کوتاه</div>
                    <div class="quick-grid">
                        <div class="quick"><i>۱</i>انتخاب کالا</div>
                        <div class="quick"><i>۲</i>داریک</div>
                        <div class="quick"><i>۳</i>شناسه</div>
                        <div class="quick"><i>۴</i>رمز OTP</div>
                    </div>
                </div>
                <div class="float-card fc2">
                    <span class="float-icon">✓</span>
                    <strong>تأیید پیامکی</strong>
                    رمز ۵ رقمی روی موبایل
                </div>
            </div>
        </div>
    </section>

    <div class="wrap">
        <div class="stats stats--titles-only">
            <div class="stat"><strong>کیف پول اعتباری</strong></div>
            <div class="stat"><strong>شناسه ۱۶رقمی</strong></div>
            <div class="stat"><strong>رمز پیامکی</strong></div>
            <div class="stat"><strong>خرید از چینی بازار</strong></div>
        </div>
    </div>

    <section class="plan-section" id="wallet">
        <div class="wrap split">
            <div class="feature-copy">
                <span class="eyebrow">داریک</span>
                <h2>پرداخت از موجودی<br>کیف پول اعتباری</h2>
                <p>
                    داریک یک درگاه اعتباری مبتنی بر کیف پول است. مبلغ سفارش از اعتبار کیف پول کسر می‌شود
                    و تأیید نهایی با رمز یک‌بارمصرف پیامک‌شده انجام می‌گیرد.
                </p>
                <ul class="checks">
                    <li>
                        <span class="check">✓</span>
                        <span><b>شناسه روی کارت یا اپ</b><br>۱۶ رقم که با <span dir="ltr">5892</span> شروع می‌شود؛ همان شناسه کاربری داریک.</span>
                    </li>
                    <li>
                        <span class="check">✓</span>
                        <span><b>تأیید امن با OTP</b><br>بعد از ثبت سفارش، رمز پنج‌رقمی به موبایل ثبت‌شده در داریک ارسال می‌شود.</span>
                    </li>
                    <li>
                        <span class="check">✓</span>
                        <span><b>همان سبد فروشگاه</b><br>قیمت و موجودی چینی بازار؛ فقط در checkout درگاه داریک را انتخاب می‌کنید.</span>
                    </li>
                </ul>
                <a class="btn" href="{{ route('products.index') }}">رفتن به فروشگاه ←</a>
            </div>
            <div class="feature-art">
                <div class="mini-bubble bubble-a">کیف پول فعال</div>
                <div class="credit-card">
                    <div class="cc-brand">داریک</div>
                    <div class="cc-chip"></div>
                    <div class="cc-number" dir="ltr">5892 &nbsp; •••• &nbsp; •••• &nbsp; ••••</div>
                    <div class="cc-foot"><span>اعتبار خرید</span><span>WALLET</span></div>
                </div>
                <div class="mini-bubble bubble-b">رمز ۵ رقمی</div>
            </div>
        </div>
    </section>

    <section class="plan-section" id="plan-darik-flow" style="padding-top:0">
        <div class="wrap">
            <div class="flow-card">
                <div class="flow-head">
                    <h2>خرید با داریک در چینی بازار چطور است؟</h2>
                    <p>مسیر خرید از فروشگاه چینی بازار با درگاه داریک</p>
                </div>
                <div class="flow-rows">
                    <div class="flow-row">
                        <div class="flow-step" data-step="1">
                            <span class="flow-step-num">۱</span>
                            <div class="flow-icon">🛍️</div>
                            <h3>انتخاب کالا</h3>
                            <p>محصولات را از چینی بازار انتخاب و به سبد خرید اضافه کنید.</p>
                        </div>
                        <div class="flow-connector flow-connector--h" aria-hidden="true"></div>
                        <div class="flow-step" data-step="2">
                            <span class="flow-step-num">۲</span>
                            <div class="flow-icon">🧾</div>
                            <h3>اطلاعات checkout</h3>
                            <p>نام، کد ملی، تماس، آدرس و کد پستی را تکمیل کنید.</p>
                        </div>
                        <div class="flow-connector flow-connector--h" aria-hidden="true"></div>
                        <div class="flow-step" data-step="3">
                            <span class="flow-step-num">۳</span>
                            <div class="flow-icon">💳</div>
                            <h3>انتخاب داریک</h3>
                            <p>درگاه داریک را بزنید و شناسه ۱۶رقمی کیف پول را وارد کنید.</p>
                        </div>
                    </div>
                    <div class="flow-row-vert" aria-hidden="true"></div>
                    <div class="flow-row">
                        <div class="flow-step" data-step="4">
                            <span class="flow-step-num">۴</span>
                            <div class="flow-icon flow-icon--accent">🔐</div>
                            <h3>تأیید رمز پیامکی</h3>
                            <p>رمز پنج‌رقمی ارسال‌شده را وارد کنید تا پرداخت تأیید شود.</p>
                        </div>
                        <div class="flow-connector flow-connector--h" aria-hidden="true"></div>
                        <div class="flow-step" data-step="5">
                            <span class="flow-step-num">۵</span>
                            <div class="flow-icon">📦</div>
                            <h3>پردازش انبار</h3>
                            <p>سفارش در انبار چینی بازار آماده‌سازی و بسته‌بندی می‌شود.</p>
                        </div>
                        <div class="flow-connector flow-connector--h" aria-hidden="true"></div>
                        <div class="flow-step" data-step="6">
                            <span class="flow-step-num">۶</span>
                            <div class="flow-icon flow-icon--accent">🚚</div>
                            <h3>ارسال</h3>
                            <p>ارسال طبق قوانین فروشگاه برای خریدهای واجد شرایط.</p>
                        </div>
                    </div>
                </div>
                <div class="flow-note" role="note">
                    <span aria-hidden="true">🛡️</span>
                    <span>موجودی واقعی کیف پول و نتیجهٔ پرداخت را سرویس داریک هنگام تأیید OTP مشخص می‌کند.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="plan-section" style="padding-top:0">
        <div class="wrap">
            <div class="section-head">
                <span class="eyebrow">پاسخ پرسش‌ها</span>
                <h2>سوالات متداول</h2>
            </div>
            <div class="faq">
                <details>
                    <summary>شناسه کیف پول داریک چیست؟</summary>
                    <p>۱۶ رقم که با <span dir="ltr">5892</span> شروع می‌شود؛ روی کارت یا اپ داریک نمایش داده می‌شود و در checkout چینی بازار وارد می‌کنید.</p>
                </details>
                <details>
                    <summary>آیا همه محصولات با داریک قابل پرداخت هستند؟</summary>
                    <p>در checkout فقط درگاه‌های فعال نمایش داده می‌شوند. اگر داریک برای سبد شما فعال باشد، می‌توانید آن را انتخاب کنید.</p>
                </details>
                <details>
                    <summary>رمز پیامکی از کجا می‌آید؟</summary>
                    <p>پس از شروع پرداخت، داریک رمز پنج‌رقمی را به موبایل ثبت‌شده در کیف پول ارسال می‌کند؛ آن را در صفحه تأیید وارد کنید.</p>
                </details>
                <details>
                    <summary>بعد از پرداخت موفق چه می‌شود؟</summary>
                    <p>سفارش در حساب کاربری چینی بازار ثبت و مثل سایر سفارش‌های پرداخت‌شده پردازش می‌شود.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="plan-section">
        <div class="wrap">
            <div class="download-box">
                <div>
                    <h2>همین حالا از چینی بازار خرید کنید</h2>
                    <p>محصول را انتخاب کنید و در checkout داریک را بزنید.</p>
                </div>
                <a class="btn" href="{{ route('home') }}">رفتن به فروشگاه ←</a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $seoTitle,
    'description' => $seoDescription,
    'url' => $canonicalUrl,
    'inLanguage' => 'fa-IR',
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => url('/'),
    ],
    'about' => [
        '@type' => 'Service',
        'name' => 'خرید اعتباری با کیف پول داریک',
        'provider' => ['@type' => 'Organization', 'name' => $siteName],
        'areaServed' => 'IR',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'شناسه کیف پول داریک چیست؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => '۱۶ رقم که با 5892 شروع می‌شود؛ روی کارت یا اپ داریک نمایش داده می‌شود و در checkout چینی بازار وارد می‌کنید.']],
        ['@type' => 'Question', 'name' => 'آیا همه محصولات با داریک قابل پرداخت هستند؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'در checkout فقط درگاه‌های فعال نمایش داده می‌شوند. اگر داریک برای سبد شما فعال باشد، می‌توانید آن را انتخاب کنید.']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
