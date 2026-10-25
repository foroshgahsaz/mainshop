@extends('layouts.shop')

@section('title', 'خرید اقساطی دیجی‌پی از چینی بازار | پرداخت اعتباری')

@php
    $seoTitle = 'خرید اقساطی دیجی‌پی از چینی بازار | پرداخت اعتباری تا '.$creditCapMillions.' میلیون';
    $seoDescription = 'راهنمای خرید لوازم خانگی از چینی بازار با درگاه دیجی‌پی؛ سقف اعتبار '.$creditCapMillions.' میلیون تومان، مراحل checkout، پردازش انبار و ارسال. محاسبه تقریبی قسط.';
    $seoKeywords = 'خرید اقساطی دیجی‌پی, دیجی‌پی چینی بازار, پرداخت اعتباری, خرید اعتباری لوازم خانگی, checkout دیجی‌پی';
    $canonicalUrl = route('plans.digipay-leasing');
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
    <link rel="stylesheet" href="{{ asset('shop/css/plans-leasing.css') }}?v=2026-10-25-shadow">
@endpush

@section('body_class', 'plan-leasing-page')

@section('content')
@php
    $creditCapMillions = (int) $creditCapMillions;
    $creditCapTomans = $creditCapMillions * 1_000_000;
    $faDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $creditFaDisplay = str_replace(range(0, 9), $faDigits, number_format($creditCapTomans));
@endphp
<div class="plan-leasing">
    <section class="hero">
        <div class="wrap hero-grid">
            <div class="hero-copy">
                <span class="eyebrow"><i></i> پرداخت با هوش</span>
                <h1>خرید از چینی بازار<br><span>ساده‌تر از همیشه با دیجی‌پی</span></h1>
                <p>
                    لوازم خانگی را از فروشگاه چینی بازار انتخاب کنید و در checkout درگاه دیجی‌پی را بزنید.
                    سقف اعتبار این طرح تا {{ str_replace(range(0, 9), $faDigits, number_format($creditCapMillions)) }} میلیون تومان است؛
                    مبلغ و اقساط نهایی در اپ دیجی‌پی تأیید می‌شود.
                </p>
                <div class="hero-actions">
                    <a class="btn" href="{{ route('products.index') }}">شروع خرید از چینی بازار <span>←</span></a>
                    <a class="btn secondary" href="#credit">آشنایی با اعتبار خرید</a>
                </div>
                <div class="trustline">
                    <div class="avatars"><span>✓</span><span>↗</span><span>★</span></div>
                    <span>انتخاب کالا در چینی بازار، پرداخت و اقساط در دیجی‌پی</span>
                </div>
            </div>

            <div class="visual" aria-label="نمای اپ دیجی‌پی">
                <div class="visual-ground" aria-hidden="true"></div>
                <div class="halo"></div>
                <div class="float-card fc1">
                    <span class="float-icon">✦</span>
                    <strong>خرید اعتباری</strong>
                    امروز انتخاب کن، طبق شرایط پرداخت کن
                </div>
                <div class="phone">
                    <div class="notch"></div>
                    <div class="phone-top"><span>۹:۴۱</span><span>●●● ▰</span></div>
                    <div class="phone-top" style="margin-top:16px">
                        <span>سلام، خوش اومدی 👋</span>
                        <span class="mini-logo">دیجی‌پی</span>
                    </div>
                    <div class="balance">
                        <small>اعتبار قابل استفاده</small>
                        <strong>{{ $creditFaDisplay }}</strong>
                        <div class="balance-foot">
                            <span>تومان</span>
                            <span>سقف طرح چینی بازار</span>
                        </div>
                    </div>
                    <div class="phone-title">خدمات پرکاربرد</div>
                    <div class="quick-grid">
                        <div class="quick"><i>⇄</i>کارت‌به‌کارت</div>
                        <div class="quick"><i>▣</i>قبض</div>
                        <div class="quick"><i>◈</i>بیمه</div>
                        <div class="quick"><i>◉</i>اعتبار</div>
                    </div>
                </div>
                <div class="float-card fc2">
                    <span class="float-icon">✓</span>
                    <strong>پرداخت آسان</strong>
                    checkout سریع در چینی بازار
                </div>
            </div>
        </div>
    </section>

    <div class="wrap">
        <div class="stats">
            <div class="stat"><strong>یک‌جا</strong><span>انتخاب تا پرداخت</span></div>
            <div class="stat"><strong>آنلاین</strong><span>checkout چینی بازار</span></div>
            <div class="stat"><strong>اقساطی</strong><span>تا {{ str_replace(range(0, 9), $faDigits, (string) $creditCapMillions) }} میلیون</span></div>
            <div class="stat"><strong>به توان تو</strong><span>پرداخت با هوش</span></div>
        </div>
    </div>

    <section class="plan-section" id="credit">
        <div class="wrap split">
            <div class="feature-copy">
                <span class="eyebrow">وام و اعتبار</span>
                <h2>برای خریدهای مهم،<br>گزینه‌های بیشتری داشته باشید</h2>
                <p>
                    در چینی بازار کالا را انتخاب کنید و با دیجی‌پی تا سقف
                    {{ str_replace(range(0, 9), $faDigits, (string) $creditCapMillions) }} میلیون تومان (در این طرح معرفی)
                    اعتباری پرداخت کنید. نوع اعتبار، تعداد اقساط و شرایط نهایی در اپ دیجی‌پی اعلام می‌شود.
                </p>
                <ul class="checks">
                    <li>
                        <span class="check">✓</span>
                        <span><b>انتخاب متناسب با نیاز</b><br>محصول را در چینی بازار ببینید؛ قبل از پرداخت، شرایط اقساط در دیجی‌پی نمایش داده می‌شود.</span>
                    </li>
                    <li>
                        <span class="check">✓</span>
                        <span><b>مدیریت شفاف‌تر پرداخت‌ها</b><br>مبلغ هر قسط را در پرونده اعتباری دیجی‌پی ببینید — عدد صفحه فقط تقریبی است.</span>
                    </li>
                    <li>
                        <span class="check">✓</span>
                        <span><b>خرید از فروشگاه چینی بازار</b><br>همان سبد و قیمت فروشگاه؛ فقط در checkout درگاه دیجی‌پی را انتخاب می‌کنید.</span>
                    </li>
                </ul>
                <a class="btn" href="https://www.mydigipay.com/hub/" target="_blank" rel="noopener noreferrer">بررسی شرایط در سایت رسمی ↗</a>
            </div>
            <div class="feature-art">
                <div class="mini-bubble bubble-a">✨ خرید راحت‌تر</div>
                <div class="credit-card">
                    <div class="cc-brand">دیجی‌پی</div>
                    <div class="cc-chip"></div>
                    <div class="cc-number">•••• &nbsp; •••• &nbsp; •••• &nbsp; ۱۴۰۴</div>
                    <div class="cc-foot"><span>اعتبار خرید</span><span>SMART PAY</span></div>
                </div>
                <div class="mini-bubble bubble-b">✓ مدیریت بهتر پرداخت‌ها</div>
            </div>
        </div>
    </section>

    <section class="plan-section" id="plan-digipay-flow" style="padding-top:0">
        <div class="wrap">
            <div class="flow-card">
                <div class="flow-head">
                    <h2>خرید با دیجی‌پی در چینی بازار چطور است؟</h2>
                    <p>مسیر خرید از فروشگاه چینی بازار با درگاه دیجی‌پی</p>
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
                            <div class="flow-icon">📱</div>
                            <h3>وارد کردن اطلاعات</h3>
                            <p>نام، کد ملی، تماس، آدرس و کد پستی را در checkout وارد کنید.</p>
                        </div>
                        <div class="flow-connector flow-connector--h" aria-hidden="true"></div>
                        <div class="flow-step" data-step="3">
                            <span class="flow-step-num">۳</span>
                            <div class="flow-icon">💳</div>
                            <h3>ورود به درگاه دیجی‌پی</h3>
                            <p>دیجی‌پی را انتخاب کنید و با کد اپ، پرداخت را نهایی کنید.</p>
                        </div>
                    </div>
                    <div class="flow-row-vert" aria-hidden="true"></div>
                    <div class="flow-row">
                        <div class="flow-step" data-step="4">
                            <span class="flow-step-num">۴</span>
                            <div class="flow-icon flow-icon--accent">✅</div>
                            <h3>ثبت نهایی</h3>
                            <p>پس از پرداخت موفق، سفارش در چینی بازار ثبت می‌شود.</p>
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
                            <h3>ارسال رایگان</h3>
                            <p>ارسال رایگان برای خریدهای واجد شرایط طبق قوانین فروشگاه.</p>
                        </div>
                    </div>
                </div>
                <div class="flow-note" role="note">
                    <span aria-hidden="true">🛡️</span>
                    <span>عدد نهایی اعتبار و قسط را سرویس دیجی‌پی در پرونده شما اعلام می‌کند.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="plan-section" id="plan-digipay-calculator">
        <div class="wrap">
            <div class="calc">
                <div>
                    <span class="eyebrow">ابزار نمایشی</span>
                    <h2>قسط ماهانه را<br>به‌صورت تقریبی ببینید</h2>
                    <p>مبلغ تا سقف {{ str_replace(range(0, 9), $faDigits, (string) $creditCapMillions) }} میلیون تومان و تعداد اقساط را تغییر دهید. این محاسبه جایگزین تأیید دیجی‌پی نیست.</p>
                </div>
                <div class="calc-box" id="digipayPlanCalc" data-cap="{{ $creditCapTomans }}">
                    <label for="planAmount">مبلغ خرید (تومان)</label>
                    <input id="planAmount" type="range" data-calc-amount min="5000000" max="{{ $creditCapTomans }}" step="500000" value="{{ min(50000000, $creditCapTomans) }}">
                    <div class="calc-range-labels">
                        <span>۵ میلیون</span>
                        <strong data-calc-amount-label>—</strong>
                        <span>{{ str_replace(range(0, 9), $faDigits, (string) $creditCapMillions) }} میلیون</span>
                    </div>
                    <label style="margin-top:18px">تعداد اقساط (ماه)</label>
                    <div class="calc-months" data-calc-months>
                        @foreach ([2, 3, 4, 6, 12] as $month)
                            <button type="button" class="calc-month @if($month === 6) is-active @endif" data-month="{{ $month }}">{{ str_replace(range(0, 9), $faDigits, (string) $month) }} ماه</button>
                        @endforeach
                    </div>
                    <div class="calc-result">
                        <div>
                            <small>قسط ماهانه تقریبی</small>
                            <strong data-calc-installment>—</strong>
                        </div>
                        <span style="font-size:25px">🧮</span>
                    </div>
                    <p class="calc-note">شرایط نهایی را در دیجی‌پی یا هنگام checkout بررسی کنید.</p>
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
                    <summary>آیا همه محصولات چینی بازار با دیجی‌پی قابل پرداخت هستند؟</summary>
                    <p>در checkout فقط درگاه‌های فعال نمایش داده می‌شوند. اگر دیجی‌پی برای سبد شما فعال باشد، می‌توانید آن را انتخاب کنید.</p>
                </details>
                <details>
                    <summary>سقف {{ str_replace(range(0, 9), $faDigits, (string) $creditCapMillions) }} میلیون تومان یعنی چه؟</summary>
                    <p>در این صفحه برای معرفی طرح، سقف {{ str_replace(range(0, 9), $faDigits, (string) $creditCapMillions) }} میلیون تومان در نظر گرفته شده است. اعتبار واقعی در اپ دیجی‌پی مشخص می‌شود.</p>
                </details>
                <details>
                    <summary>بعد از پرداخت موفق چه می‌شود؟</summary>
                    <p>سفارش در حساب کاربری چینی بازار ثبت و مثل سایر سفارش‌های پرداخت‌شده ارسال می‌شود.</p>
                </details>
                <details>
                    <summary>آیا مبلغ قسط در ابزار این صفحه دقیق است؟</summary>
                    <p>خیر. فقط تقسیم ساده مبلغ است و کارمزد یا شرایط طرح را لحاظ نمی‌کند.</p>
                </details>
            </div>
        </div>
    </section>

    <section class="plan-section">
        <div class="wrap">
            <div class="download-box">
                <div>
                    <h2>همین حالا از چینی بازار خرید کنید</h2>
                    <p>محصول را انتخاب کنید و در checkout دیجی‌پی را بزنید.</p>
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
        'name' => 'خرید اقساطی با دیجی‌پی',
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
        ['@type' => 'Question', 'name' => 'آیا همه محصولات چینی بازار با دیجی‌پی قابل پرداخت هستند؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'در checkout فقط درگاه‌های فعال نمایش داده می‌شوند. اگر دیجی‌پی برای سبد شما فعال باشد، می‌توانید آن را انتخاب کنید.']],
        ['@type' => 'Question', 'name' => 'سقف اعتبار ۲۰۰ میلیون تومان یعنی چه؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'در این صفحه برای معرفی طرح، سقف ۲۰۰ میلیون تومان در نظر گرفته شده است. اعتبار واقعی در اپ دیجی‌پی مشخص می‌شود.']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script>
(function () {
    const faNum = (n) => Math.round(n).toLocaleString('fa-IR');
    const root = document.getElementById('digipayPlanCalc');
    if (root) {
        const amount = root.querySelector('[data-calc-amount]');
        const amountLabel = root.querySelector('[data-calc-amount-label]');
        const installmentEl = root.querySelector('[data-calc-installment]');
        const monthButtons = root.querySelectorAll('[data-calc-months] [data-month]');
        let months = 6;
        function updateCalc() {
            const a = Number(amount.value);
            amountLabel.textContent = faNum(a) + ' تومان';
            installmentEl.textContent = faNum(months > 0 ? a / months : 0) + ' تومان';
        }
        amount.addEventListener('input', updateCalc);
        monthButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                monthButtons.forEach((b) => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                months = Number(btn.dataset.month);
                updateCalc();
            });
        });
        updateCalc();
    }
})();
</script>
@endpush
