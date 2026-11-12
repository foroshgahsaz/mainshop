@extends('layouts.shop')

@section('title', 'درخواست نمایندگی | '.$siteName)

@php
    $seoDescription = 'ثبت درخواست نمایندگی '.$siteName.'؛ فرم مرحله‌ای و اطلاعات تماس دفتر منطقه تهران.';
    $canonicalUrl = route('representative.branch.register');
@endphp

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
        'title' => 'درخواست نمایندگی | '.$siteName,
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'image' => $seoImage ?? null,
        'type' => 'website',
    ])" />
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/rep-branch-register.css') }}?v=2026-11-07">
@endpush

@section('body_class', 'rep-branch-register-page')

@section('content')
<div class="rep-branch-register" id="repBranchRegisterRoot">
    <header class="rep-branch-register__top">
        <p class="rep-branch-register__brand">
            <span class="rep-branch-register__brand-dot" aria-hidden="true"></span>
            <span>{{ $siteName }}</span>
        </p>
        <h1 class="rep-branch-register__title">درخواست نمایندگی</h1>
        <p class="rep-branch-register__lead">
            اطلاعات را مرحله‌به‌مرحله وارد کنید. کارشناسان منطقه پرونده را تکمیل و نمایندگی را فعال می‌کنند.
        </p>
        <p class="rep-branch-register__notice">
            با ثبت درخواست، حساب نمایندگی ساخته نمی‌شود. فقط پرونده برای منطقه شما ثبت می‌شود تا تیم {{ $siteName }} تماس بگیرد و پس از تکمیل مدارک، نمایندگی را فعال کند.
        </p>
    </header>

    <div class="rbp-wrap">
        <div class="rep-branch-register__card" data-wizard-card>
            <div class="rep-branch-register__steps" role="tablist" aria-label="مراحل فرم">
                @foreach (['مشخصات', 'مغازه و منطقه', 'تأیید'] as $index => $label)
                    <div
                        class="rep-branch-register__step-pill{{ $index === 0 ? ' is-active' : '' }}"
                        data-step-pill="{{ $index }}"
                    >
                        <span class="rep-branch-register__step-num">{{ $index + 1 }}</span>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>

            <form id="repBranchRegisterForm" novalidate>
                <div data-step-panel="0">
                    <div class="rep-branch-register__grid">
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-first-name">نام</label>
                            <input class="rep-branch-register__input" id="rbp-first-name" name="first_name" type="text" autocomplete="given-name" required>
                        </div>
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-last-name">نام خانوادگی</label>
                            <input class="rep-branch-register__input" id="rbp-last-name" name="last_name" type="text" autocomplete="family-name" required>
                        </div>
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-mobile">موبایل</label>
                            <input class="rep-branch-register__input" id="rbp-mobile" name="mobile" type="tel" inputmode="tel" dir="ltr" placeholder="09xxxxxxxxx" required>
                        </div>
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-national-code">کد ملی</label>
                            <input class="rep-branch-register__input" id="rbp-national-code" name="national_code" type="text" inputmode="numeric" maxlength="10" required>
                        </div>
                    </div>
                </div>

                <div class="rep-branch-register__hidden" data-step-panel="1" hidden>
                    <div class="rep-branch-register__grid">
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-province">استان</label>
                            <select class="rep-branch-register__select" id="rbp-province" name="province" required>
                                <option value="">انتخاب استان</option>
                                <option value="تهران" selected>تهران</option>
                            </select>
                        </div>
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-city">شهر</label>
                            <input class="rep-branch-register__input" id="rbp-city" name="city" type="text" required>
                        </div>
                        <div class="rep-branch-register__field rep-branch-register__field--full">
                            <label class="rep-branch-register__label" for="rbp-address">آدرس مغازه / نقطه فروش</label>
                            <textarea class="rep-branch-register__textarea" id="rbp-address" name="address" required></textarea>
                        </div>
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-experience">سابقه فعالیت (سال)</label>
                            <input class="rep-branch-register__input" id="rbp-experience" name="experience_years" type="number" min="0" max="50">
                        </div>
                        <div class="rep-branch-register__field">
                            <label class="rep-branch-register__label" for="rbp-shop-name">نام واحد صنفی</label>
                            <input class="rep-branch-register__input" id="rbp-shop-name" name="shop_name" type="text">
                        </div>
                    </div>
                </div>

                <div class="rep-branch-register__hidden" data-step-panel="2" hidden>
                    <div class="rep-branch-register__field rep-branch-register__field--full">
                        <label class="rep-branch-register__label" for="rbp-notes">توضیحات تکمیلی</label>
                        <textarea class="rep-branch-register__textarea" id="rbp-notes" name="notes" placeholder="مثلاً برندها یا حوزه فعالیت مورد علاقه"></textarea>
                    </div>
                    <label class="rep-branch-register__checkbox">
                        <input type="checkbox" name="accept_terms" id="rbp-terms" required>
                        <span>صحت اطلاعات را تأیید می‌کنم و مایلم کارشناسان {{ $siteName }} برای تکمیل پرونده با من تماس بگیرند.</span>
                    </label>
                </div>

                <div class="rep-branch-register__hidden" data-success-panel hidden>
                    <div class="rep-branch-register__success">
                        <div class="rep-branch-register__success-icon" aria-hidden="true">✓</div>
                        <h2>پیش‌نمایش ثبت شد</h2>
                        <p class="text-sm text-gray-600 mt-2">
                            این صفحه فعلاً نمایشی است؛ درخواست شما در سیستم ذخیره نشد.
                            به‌زودی ثبت آنلاین و پیگیری از همین آدرس فعال می‌شود.
                        </p>
                    </div>
                </div>

                <div class="rep-branch-register__actions" data-wizard-actions>
                    <button type="button" class="rep-branch-register__btn rep-branch-register__btn--ghost" data-btn-prev disabled>
                        مرحله قبل
                    </button>
                    <button type="button" class="rep-branch-register__btn rep-branch-register__btn--primary" data-btn-next>
                        ادامه
                    </button>
                    <button type="submit" class="rep-branch-register__btn rep-branch-register__btn--primary rep-branch-register__hidden" data-btn-submit>
                        ثبت درخواست نمایندگی
                    </button>
                </div>
            </form>
        </div>
    </div>

    <section class="rep-branch-register__regions" id="sales-regions" aria-labelledby="regions-heading">
        <div class="rbp-wrap">
            <header class="rep-branch-register__regions-header">
                <h2 id="regions-heading">مناطق فروش</h2>
                <p>پس از ثبت درخواست، همین شبکه منطقه‌ای پرونده شما را پیگیری می‌کند.</p>
                <div class="rep-branch-register__regions-meta">
                    <span>بروزرسانی: {{ $regionsMeta['updated_label'] }}</span>
                    <span>{{ $regionsMeta['region_count'] }} منطقه</span>
                </div>
            </header>

            <div class="rep-branch-register__office-grid">
                <article class="rep-branch-register__office">
                    <span class="rep-branch-register__office-badge">{{ $tehranOffice['leasing_badge'] }} {{ $tehranOffice['leasing_label'] }}</span>
                    <h3>{{ $tehranOffice['title'] }}</h3>

                    <div class="rep-branch-register__person">
                        <span class="rep-branch-register__avatar" aria-hidden="true">{{ mb_substr($tehranOffice['manager']['name'], 0, 1) }}</span>
                        <div>
                            <div class="rep-branch-register__person-name">بنکدار {{ $tehranOffice['manager']['name'] }}</div>
                            <div class="rep-branch-register__person-phone">{{ $tehranOffice['manager']['phone'] }}</div>
                        </div>
                    </div>

                    <p class="rep-branch-register__section-label">کارشناسان ({{ count($tehranOffice['specialists']) }})</p>
                    <ul class="rep-branch-register__list">
                        @foreach ($tehranOffice['specialists'] as $specialist)
                            <li>
                                <span>{{ $specialist['name'] }}</span>
                                <span class="rep-branch-register__person-phone">{{ $specialist['phone'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <p class="rep-branch-register__section-label">استان‌ها ({{ count($tehranOffice['provinces']) }})</p>
                    <div class="rep-branch-register__provinces">
                        @foreach ($tehranOffice['provinces'] as $province)
                            <span class="rep-branch-register__province">{{ $province }}</span>
                        @endforeach
                    </div>
                </article>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('repBranchRegisterForm');
    if (!form) return;

    var step = 0;
    var totalSteps = 3;
    var panels = form.querySelectorAll('[data-step-panel]');
    var pills = document.querySelectorAll('[data-step-pill]');
    var btnPrev = form.querySelector('[data-btn-prev]');
    var btnNext = form.querySelector('[data-btn-next]');
    var btnSubmit = form.querySelector('[data-btn-submit]');
    var actions = form.querySelector('[data-wizard-actions]');
    var successPanel = form.querySelector('[data-success-panel]');

    function syncUi() {
        panels.forEach(function (panel, index) {
            var active = index === step;
            panel.hidden = !active;
            panel.classList.toggle('rep-branch-register__hidden', !active);
        });
        pills.forEach(function (pill, index) {
            pill.classList.toggle('is-active', index === step);
            pill.classList.toggle('is-done', index < step);
        });
        btnPrev.disabled = step === 0;
        var onLast = step === totalSteps - 1;
        btnNext.classList.toggle('rep-branch-register__hidden', onLast);
        btnSubmit.classList.toggle('rep-branch-register__hidden', !onLast);
    }

    function fieldsForStep(s) {
        return Array.prototype.slice.call(panels[s].querySelectorAll('input, select, textarea'));
    }

    function validateStep(s) {
        var ok = true;
        fieldsForStep(s).forEach(function (el) {
            if (!el.checkValidity()) {
                ok = false;
                el.reportValidity();
            }
        });
        return ok;
    }

    btnNext.addEventListener('click', function () {
        if (!validateStep(step)) return;
        if (step < totalSteps - 1) {
            step += 1;
            syncUi();
        }
    });

    btnPrev.addEventListener('click', function () {
        if (step > 0) {
            step -= 1;
            syncUi();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!validateStep(step)) return;
        panels.forEach(function (panel) {
            panel.hidden = true;
            panel.classList.add('rep-branch-register__hidden');
        });
        if (actions) actions.classList.add('rep-branch-register__hidden');
        pills.forEach(function (pill) {
            pill.classList.remove('is-active');
            pill.classList.add('is-done');
        });
        if (successPanel) {
            successPanel.hidden = false;
            successPanel.classList.remove('rep-branch-register__hidden');
        }
    });

    syncUi();
})();
</script>
@endpush
