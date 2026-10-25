@extends('layouts.shop')

@section('title', 'خرید اقساطی با دیجی‌پی | چینی بازار')

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
        'title' => 'طرح خرید اقساطی دیجی‌پی در چینی بازار',
        'description' => 'خرید لوازم خانگی از چینی بازار با پرداخت اعتباری دیجی‌پی؛ سقف اعتبار تا '.$creditCapMillions.' میلیون تومان. راهنمای مراحل خرید و محاسبه تقریبی قسط.',
    ])" />
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/plans-leasing.css') }}">
@endpush

@section('body_class', 'plan-leasing-page')

@section('content')
@php
    $creditCapMillions = (int) $creditCapMillions;
    $creditCapTomans = $creditCapMillions * 1_000_000;
@endphp
<div class="plan-leasing">
    <section class="plan-leasing__hero">
        <div class="container mx-auto px-4">
            <div class="plan-leasing__hero-grid">
                <div>
                    <span class="plan-leasing__eyebrow">
                        <span aria-hidden="true">◆</span>
                        پرداخت اعتباری دیجی‌پی
                    </span>
                    <h1 class="plan-leasing__title">خرید اقساطی از چینی بازار با دیجی‌پی</h1>
                    <p class="plan-leasing__lead">
                        کالاهای مورد نیاز خانه را از فروشگاه چینی بازار انتخاب کنید و در checkout درگاه
                        <strong>دیجی‌پی</strong> را بزنید. سقف اعتبار قابل استفاده در این صفحه
                        <strong>{{ number_format($creditCapMillions) }} میلیون تومان</strong> در نظر گرفته شده است؛
                        مبلغ نهایی و تعداد اقساط را سرویس دیجی‌پی در پرونده اعتباری شما تأیید می‌کند.
                    </p>
                    <div class="plan-leasing__actions">
                        <a href="{{ route('products.index') }}" class="plan-leasing__btn-primary">مشاهده محصولات</a>
                        <a href="#plan-digipay-flow" class="plan-leasing__btn-ghost">مراحل خرید</a>
                    </div>
                    <div class="plan-leasing__pill-row">
                        <div class="plan-leasing__pill">خرید آنلاین</div>
                        <div class="plan-leasing__pill">پرداخت اعتباری</div>
                        <div class="plan-leasing__pill">اقساط شفاف</div>
                        <div class="plan-leasing__pill">تحویل درب منزل</div>
                    </div>
                </div>
                <div class="plan-leasing__hero-visual" aria-hidden="true">
                    <div class="plan-leasing__phone">
                        <div class="plan-leasing__phone-label">سقف اعتبار (نمایشی)</div>
                        <div class="plan-leasing__phone-amount">{{ number_format($creditCapMillions) }} <span style="font-size:1rem;font-weight:700">میلیون تومان</span></div>
                        <div class="plan-leasing__phone-sub">پرداخت از طریق اپ دیجی‌پی</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="plan-leasing__section">
        <div class="container mx-auto px-4">
            <h2 class="plan-leasing__section-title">چرا دیجی‌پی در چینی بازار؟</h2>
            <p class="plan-leasing__section-lead">همه مراحل داخل سایت چینی بازار انجام می‌شود؛ فقط مرحله پرداخت به درگاه دیجی‌پی هدایت می‌شوید.</p>
            <div class="plan-leasing__features">
                <article class="plan-leasing__feature">
                    <div class="plan-leasing__feature-icon" aria-hidden="true">🛒</div>
                    <h3 class="plan-leasing__feature-title">سبد خرید چینی بازار</h3>
                    <p class="plan-leasing__feature-text">محصولات را مثل هر خرید عادی انتخاب کنید؛ قیمت‌ها و موجودی همان فروشگاه است.</p>
                </article>
                <article class="plan-leasing__feature">
                    <div class="plan-leasing__feature-icon" aria-hidden="true">💳</div>
                    <h3 class="plan-leasing__feature-title">درگاه دیجی‌پی</h3>
                    <p class="plan-leasing__feature-text">در checkout گزینه دیجی‌پی را انتخاب کنید و با تأیید هویت در اپ، پرداخت را نهایی کنید.</p>
                </article>
                <article class="plan-leasing__feature">
                    <div class="plan-leasing__feature-icon" aria-hidden="true">📊</div>
                    <h3 class="plan-leasing__feature-title">اقساط مشخص</h3>
                    <p class="plan-leasing__feature-text">قبل از تأیید نهایی، جزئیات اقساط در دیجی‌پی نمایش داده می‌شود؛ عدد این صفحه فقط تقریبی است.</p>
                </article>
                <article class="plan-leasing__feature">
                    <div class="plan-leasing__feature-icon" aria-hidden="true">🚚</div>
                    <h3 class="plan-leasing__feature-title">ارسال سفارش</h3>
                    <p class="plan-leasing__feature-text">پس از پرداخت موفق، سفارش در چینی بازار ثبت و وارد فرآیند ارسال می‌شود.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="plan-leasing__section plan-leasing__section--muted" id="plan-digipay-flow">
        <div class="container mx-auto px-4">
            <div class="plan-leasing__flow-card">
                <div class="plan-leasing__flow-head">
                    <div>
                        <h2>خرید با دیجی‌پی در چینی بازار چطور است؟</h2>
                    </div>
                    <p>مسیر خرید از فروشگاه چینی بازار با درگاه دیجی‌پی</p>
                </div>

                <div class="plan-leasing__flow-steps">
                    <div class="plan-leasing__flow-step">
                        <span class="plan-leasing__flow-step-num">۱</span>
                        <div class="plan-leasing__flow-icon" aria-hidden="true">🛍️</div>
                        <h3>انتخاب کالا</h3>
                        <p>محصولات مورد نظرتان را از میان کالاهای چینی بازار انتخاب و به سبد خرید اضافه کنید.</p>
                    </div>
                    <div class="plan-leasing__flow-step">
                        <span class="plan-leasing__flow-step-num">۲</span>
                        <div class="plan-leasing__flow-icon" aria-hidden="true">📱</div>
                        <h3>وارد کردن اطلاعات</h3>
                        <p>نام، کد ملی، شماره تماس، آدرس و کد پستی را در checkout وارد کنید.</p>
                    </div>
                    <div class="plan-leasing__flow-step">
                        <span class="plan-leasing__flow-step-num">۳</span>
                        <div class="plan-leasing__flow-icon" aria-hidden="true">💳</div>
                        <h3>ورود به درگاه دیجی‌پی</h3>
                        <p>درگاه دیجی‌پی را انتخاب کنید، کد احراز هویت را در اپ بزنید و خرید را نهایی کنید.</p>
                    </div>
                    <div class="plan-leasing__flow-step plan-leasing__flow-step--wide">
                        <span class="plan-leasing__flow-step-num">۴</span>
                        <div class="plan-leasing__flow-icon plan-leasing__flow-icon--accent" aria-hidden="true">🚚</div>
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
                     data-cap="{{ $creditCapTomans }}"
                     data-cap-label="{{ number_format($creditCapMillions) }}">
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
                <a href="{{ route('home') }}" class="plan-leasing__btn-primary" style="background:#fff;color:var(--plan-purple-dark)">رفتن به فروشگاه</a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.getElementById('digipayPlanCalc');
    if (!root) return;

    const amountInput = root.querySelector('[data-calc-amount]');
    const amountLabel = root.querySelector('[data-calc-amount-label]');
    const installmentEl = root.querySelector('[data-calc-installment]');
    const monthButtons = root.querySelectorAll('[data-calc-months] [data-month]');
    let months = 6;

    const formatTomans = (n) => new Intl.NumberFormat('fa-IR').format(Math.round(n)) + ' تومان';

    function recalc() {
        const amount = Number(amountInput.value);
        const installment = months > 0 ? amount / months : 0;
        amountLabel.textContent = formatTomans(amount);
        installmentEl.textContent = formatTomans(installment);
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
