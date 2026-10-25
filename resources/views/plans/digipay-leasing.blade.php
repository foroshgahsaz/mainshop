@extends('layouts.shop')

@section('title', 'خرید اقساطی با دیجی‌پی | چینی بازار')

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
        'title' => 'طرح خرید اقساطی دیجی‌پی در چینی بازار',
        'description' => 'خرید لوازم خانگی از چینی بازار با پرداخت اعتباری دیجی‌پی؛ سقف اعتبار تا '.$creditCapMillions.' میلیون تومان. راهنمای مراحل خرید و محاسبه تقریبی قسط.',
    ])" />
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/plans-leasing.css') }}?v=2026-10-25-2">
@endpush

@section('body_class', 'plan-leasing-page')

@section('content')
@php
    $creditCapMillions = (int) $creditCapMillions;
    $creditCapTomans = $creditCapMillions * 1_000_000;
@endphp
<div class="plan-leasing">
    {{-- Hero (Digipay hub style) --}}
    <section class="plan-leasing__hero">
        <div class="container mx-auto px-4">
            <div class="plan-leasing__hero-grid">
                <div class="plan-leasing__hero-copy">
                    <span class="plan-leasing__eyebrow">پرداخت، باهوش</span>
                    <h1 class="plan-leasing__title">
                        خرید از چینی بازار،
                        <span class="plan-leasing__title-accent">ساده‌تر از همیشه</span>
                    </h1>
                    <p class="plan-leasing__lead">
                        لوازم خانگی را از چینی بازار انتخاب کنید و در checkout درگاه دیجی‌پی را بزنید.
                        سقف اعتبار معرفی‌شده در این صفحه
                        <strong>{{ number_format($creditCapMillions) }} میلیون تومان</strong> است؛
                        مبلغ و اقساط نهایی در اپ دیجی‌پی تأیید می‌شود.
                    </p>
                    <div class="plan-leasing__actions">
                        <a href="{{ route('products.index') }}" class="plan-leasing__btn-primary">
                            شروع خرید از چینی بازار
                            <span class="plan-leasing__btn-arrow" aria-hidden="true">←</span>
                        </a>
                        <a href="#plan-digipay-credit" class="plan-leasing__btn-outline">آشنایی با اعتبار خرید</a>
                    </div>
                    <p class="plan-leasing__hero-trust">
                        <span class="plan-leasing__hero-trust-icons" aria-hidden="true">✦ ✓ ★</span>
                        خرید اعتباری از فروشگاه چینی بازار — پرداخت و اقساط در دیجی‌پی
                    </p>
                </div>

                <div class="plan-leasing__hero-visual" aria-hidden="true">
                    <div class="plan-leasing__float plan-leasing__float--1">
                        <span class="plan-leasing__float-icon">+</span>
                        خرید اعتباری
                    </div>
                    <div class="plan-leasing__float plan-leasing__float--2">
                        <span class="plan-leasing__float-icon plan-leasing__float-icon--ok">✓</span>
                        پرداخت آسان
                    </div>
                    <div class="plan-leasing__phone-shell">
                        <div class="plan-leasing__phone-notch"></div>
                        <div class="plan-leasing__phone-screen">
                            <div class="plan-leasing__phone-top">
                                <span class="plan-leasing__phone-brand">DigiPay</span>
                            </div>
                            <div class="plan-leasing__phone-credit">
                                <span class="plan-leasing__phone-credit-label">اعتبار قابل استفاده</span>
                                <span class="plan-leasing__phone-credit-amount">{{ number_format($creditCapMillions) }}</span>
                                <span class="plan-leasing__phone-credit-unit">میلیون تومان</span>
                            </div>
                            <div class="plan-leasing__phone-icons">
                                <span></span><span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="plan-leasing__hero-stats">
                <div class="plan-leasing__hero-stat">
                    <strong>یکجا</strong>
                    <span>انتخاب کالا تا پرداخت</span>
                </div>
                <div class="plan-leasing__hero-stat">
                    <strong>آنلاین</strong>
                    <span>checkout چینی بازار</span>
                </div>
                <div class="plan-leasing__hero-stat">
                    <strong>اقساطی</strong>
                    <span>تا سقف {{ number_format($creditCapMillions) }}M</span>
                </div>
                <div class="plan-leasing__hero-stat">
                    <strong>هوشمند</strong>
                    <span>تأیید در اپ دیجی‌پی</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Credit section (reference image 2) --}}
    <section class="plan-leasing__section" id="plan-digipay-credit">
        <div class="container mx-auto px-4">
            <div class="plan-leasing__split">
                <div class="plan-leasing__split-visual" aria-hidden="true">
                    <div class="plan-leasing__card-scene">
                        <div class="plan-leasing__card-tag plan-leasing__card-tag--1">خرید راحت‌تر</div>
                        <div class="plan-leasing__card-tag plan-leasing__card-tag--2">مدیریت بهتر پرداخت‌ها</div>
                        <div class="plan-leasing__credit-card">
                            <div class="plan-leasing__credit-card-top">
                                <span>دیجی‌پی</span>
                                <span class="plan-leasing__credit-chip"></span>
                            </div>
                            <div class="plan-leasing__credit-card-num">•••• •••• •••• ۱۴۰۴</div>
                            <div class="plan-leasing__credit-card-bottom">
                                <span>SMART PAY</span>
                                <span>اعتبار خرید</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="plan-leasing__split-copy">
                    <span class="plan-leasing__eyebrow plan-leasing__eyebrow--soft">وام و اعتبار</span>
                    <h2 class="plan-leasing__split-title">برای خریدهای مهم، گزینه‌های بیشتری داشته باشید</h2>
                    <p class="plan-leasing__split-lead">
                        با دیجی‌پی می‌توانید سبد خرید چینی بازار را تا سقف
                        {{ number_format($creditCapMillions) }} میلیون تومان (در این طرح معرفی) اعتباری پرداخت کنید
                        و اقساط را در اپ مدیریت کنید.
                    </p>
                    <ul class="plan-leasing__checklist">
                        <li>
                            <span class="plan-leasing__check" aria-hidden="true">✓</span>
                            <div>
                                <strong>انتخاب متناسب با نیاز</strong>
                                <p>کالا را در چینی بازار انتخاب کنید؛ قبل از نهایی کردن، شرایط اقساط در دیجی‌پی نمایش داده می‌شود.</p>
                            </div>
                        </li>
                        <li>
                            <span class="plan-leasing__check" aria-hidden="true">✓</span>
                            <div>
                                <strong>مدیریت شفاف‌تر پرداخت‌ها</strong>
                                <p>مبلغ هر قسط و زمان بازپرداخت را در پرونده اعتباری دیجی‌پی ببینید — عدد این صفحه فقط تقریبی است.</p>
                            </div>
                        </li>
                        <li>
                            <span class="plan-leasing__check" aria-hidden="true">✓</span>
                            <div>
                                <strong>خرید از فروشگاه چینی بازار</strong>
                                <p>همان سبد و همان قیمت فروشگاه؛ فقط روش پرداخت در checkout «دیجی‌پی» انتخاب می‌شود.</p>
                            </div>
                        </li>
                    </ul>
                    <a href="https://www.mydigipay.com/hub/" class="plan-leasing__btn-primary plan-leasing__btn-primary--compact" target="_blank" rel="noopener noreferrer">
                        بررسی شرایط در سایت رسمی دیجی‌پی
                        <span class="plan-leasing__btn-arrow" aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Purchase flow RTL + connectors --}}
    <section class="plan-leasing__section plan-leasing__section--muted" id="plan-digipay-flow">
        <div class="container mx-auto px-4">
            <div class="plan-leasing__flow-card">
                <div class="plan-leasing__flow-head">
                    <h2>خرید با دیجی‌پی در چینی بازار چطور است؟</h2>
                    <p>مسیر خرید از فروشگاه چینی بازار با درگاه دیجی‌پی</p>
                </div>

                <div class="plan-leasing__flow-path">
                    <div class="plan-leasing__flow-step" data-step="1">
                        <span class="plan-leasing__flow-step-num">۱</span>
                        <div class="plan-leasing__flow-icon">🛍️</div>
                        <h3>انتخاب کالا</h3>
                        <p>محصولات مورد نظرتان را از میان کالاهای چینی بازار انتخاب و به سبد خرید اضافه کنید.</p>
                    </div>
                    <div class="plan-leasing__flow-connector plan-leasing__flow-connector--h" aria-hidden="true"></div>
                    <div class="plan-leasing__flow-step" data-step="2">
                        <span class="plan-leasing__flow-step-num">۲</span>
                        <div class="plan-leasing__flow-icon">📱</div>
                        <h3>وارد کردن اطلاعات</h3>
                        <p>نام، کد ملی، شماره تماس، آدرس و کد پستی را در checkout وارد کنید.</p>
                    </div>
                    <div class="plan-leasing__flow-connector plan-leasing__flow-connector--h" aria-hidden="true"></div>
                    <div class="plan-leasing__flow-step" data-step="3">
                        <span class="plan-leasing__flow-step-num">۳</span>
                        <div class="plan-leasing__flow-icon">💳</div>
                        <h3>ورود به درگاه دیجی‌پی</h3>
                        <p>درگاه دیجی‌پی را انتخاب کنید، کد احراز هویت را در اپ بزنید و خرید را نهایی کنید.</p>
                    </div>
                    <div class="plan-leasing__flow-connector plan-leasing__flow-connector--v" aria-hidden="true"></div>
                    <div class="plan-leasing__flow-step plan-leasing__flow-step--final" data-step="4">
                        <span class="plan-leasing__flow-step-num">۴</span>
                        <div class="plan-leasing__flow-icon plan-leasing__flow-icon--accent">🚚</div>
                        <h3>ثبت نهایی</h3>
                        <p>پس از نهایی شدن پرداخت، سفارش در چینی بازار ثبت می‌شود و وارد فرآیند ارسال می‌گردد.</p>
                    </div>
                </div>

                <div class="plan-leasing__flow-note" role="note">
                    <span aria-hidden="true">🛡️</span>
                    <span>عدد نهایی اعتبار و قسط را سرویس دیجی‌پی در پرونده شما اعلام می‌کند؛ محاسبه زیر فقط برای آشنایی اولیه است.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="plan-leasing__section" id="plan-digipay-calculator">
        <div class="container mx-auto px-4">
            <div class="plan-leasing__calc">
                <div class="plan-leasing__calc-panel">
                    <h2>قسط ماهانه را به‌صورت تقریبی ببینید</h2>
                    <p>
                        مبلغ خرید را تا سقف {{ number_format($creditCapMillions) }} میلیون تومان انتخاب کنید و تعداد ماه را مشخص کنید.
                        نرخ و شرایط واقعی هنگام پرداخت در دیجی‌پی نمایش داده می‌شود.
                    </p>
                </div>
                <div class="plan-leasing__calc-box"
                     id="digipayPlanCalc"
                     data-cap="{{ $creditCapTomans }}">
                    <div class="plan-leasing__calc-label">
                        <span>مبلغ خرید (تومان)</span>
                        <span class="plan-leasing__calc-value" data-calc-amount-label>—</span>
                    </div>
                    <input type="range"
                           class="plan-leasing__calc-range"
                           data-calc-amount
                           min="5000000"
                           max="{{ $creditCapTomans }}"
                           step="500000"
                           value="{{ min(50000000, $creditCapTomans) }}"
                           aria-label="مبلغ خرید">

                    <div class="plan-leasing__calc-label">
                        <span>تعداد اقساط (ماه)</span>
                    </div>
                    <div class="plan-leasing__calc-months" data-calc-months role="group" aria-label="تعداد اقساط">
                        @foreach ([2, 3, 4, 6, 12] as $month)
                            <button type="button"
                                    class="plan-leasing__calc-month @if($month === 6) is-active @endif"
                                    data-month="{{ $month }}">{{ $month }} ماه</button>
                        @endforeach
                    </div>

                    <div class="plan-leasing__calc-result">
                        <div class="plan-leasing__calc-result-label">قسط ماهانه تقریبی</div>
                        <div class="plan-leasing__calc-result-amount" data-calc-installment>—</div>
                    </div>
                    <p class="plan-leasing__calc-disclaimer">
                        سقف اعتبار این صفحه: {{ number_format($creditCapMillions) }} میلیون تومان.
                        این ابزار جایگزین تأیید دیجی‌پی نیست.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="plan-leasing__section plan-leasing__section--muted">
        <div class="container mx-auto px-4">
            <h2 class="plan-leasing__section-title">سوالات متداول</h2>
            <p class="plan-leasing__section-lead">پاسخ کوتاه به رایج‌ترین سوالات درباره خرید با دیجی‌پی از چینی بازار</p>
            <div class="plan-leasing__faq">
                @foreach ([
                    ['q' => 'آیا همه محصولات چینی بازار با دیجی‌پی قابل پرداخت هستند؟', 'a' => 'در checkout فقط درگاه‌های فعال نمایش داده می‌شوند. اگر دیجی‌پی برای سبد شما فعال باشد، می‌توانید آن را انتخاب کنید.'],
                    ['q' => 'سقف اعتبار ۲۰۰ میلیون تومان یعنی چه؟', 'a' => 'در این صفحه برای محاسبه و معرفی، سقف ۲۰۰ میلیون تومان در نظر گرفته شده است. اعتبار واقعی شما در اپ دیجی‌پی و هنگام پرداخت مشخص می‌شود.'],
                    ['q' => 'بعد از پرداخت موفق چه می‌شود؟', 'a' => 'سفارش در پنل کاربری چینی بازار ثبت می‌شود و مثل سایر سفارش‌های پرداخت‌شده وارد مرحله آماده‌سازی و ارسال می‌گردد.'],
                    ['q' => 'اگر پرداخت نیمه‌کاره بماند چه کنم؟', 'a' => 'از بخش سفارش‌ها در حساب کاربری می‌توانید وضعیت را ببینید یا دوباره برای پرداخت اقدام کنید (مطابق قوانین زمانی سفارش در سایت).'],
                ] as $item)
                    <div class="plan-leasing__faq-item">
                        <button type="button" class="plan-leasing__faq-q" aria-expanded="false">
                            <span>{{ $item['q'] }}</span>
                            <span class="plan-leasing__faq-chevron" aria-hidden="true">▼</span>
                        </button>
                        <div class="plan-leasing__faq-a">{{ $item['a'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="plan-leasing__section">
        <div class="container mx-auto px-4">
            <div class="plan-leasing__cta-band">
                <h2>همین حالا خرید را شروع کنید</h2>
                <p>محصول را انتخاب کنید، در checkout دیجی‌پی را بزنید و اقساط را در اپ تأیید کنید.</p>
                <a href="{{ route('home') }}" class="plan-leasing__btn-primary plan-leasing__btn-primary--on-dark">رفتن به فروشگاه</a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.getElementById('digipayPlanCalc');
    if (root) {
        const amountInput = root.querySelector('[data-calc-amount]');
        const amountLabel = root.querySelector('[data-calc-amount-label]');
        const installmentEl = root.querySelector('[data-calc-installment]');
        const monthButtons = root.querySelectorAll('[data-calc-months] [data-month]');
        let months = 6;
        const formatTomans = (n) => new Intl.NumberFormat('fa-IR').format(Math.round(n)) + ' تومان';
        function recalc() {
            const amount = Number(amountInput.value);
            amountLabel.textContent = formatTomans(amount);
            installmentEl.textContent = formatTomans(months > 0 ? amount / months : 0);
        }
        amountInput.addEventListener('input', recalc);
        monthButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                monthButtons.forEach((b) => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                months = Number(btn.dataset.month);
                recalc();
            });
        });
        recalc();
    }

    document.querySelectorAll('.plan-leasing__faq-q').forEach((btn) => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.plan-leasing__faq-item');
            const open = item.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
})();
</script>
@endpush
