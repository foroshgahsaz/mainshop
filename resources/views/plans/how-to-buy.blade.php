@extends('layouts.shop')

@section('title', 'راهنمای خرید اقساطی | '.$siteName)

@php
    $seoDescription = 'مقایسهٔ طرح‌های خرید اقساطی از '.$siteName.'؛ سقف وام، اقساط، ضمانت و نحوهٔ پرداخت (BNPL تجارت، کالاپی، نوپی، بتا، تارا، دیجی‌پی، دیبا کارت).';
    $canonicalUrl = route('plans.how-to-buy');
@endphp

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
        'title' => 'روش‌های خرید اقساطی از '.$siteName,
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'image' => $seoImage ?? null,
        'type' => 'website',
    ])" />
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/how-to-buy.css') }}?v=2026-11-07-end">
@endpush

@section('body_class', 'how-to-buy-page')

@section('content')
<div class="how-to-buy">
    <header class="how-to-buy__hero">
        <div class="htb-wrap">
            <span class="how-to-buy__eyebrow">{{ $siteName }}</span>
            <h1>روش‌های خرید اقساطی از <em>{{ $siteName }}</em></h1>
            <p>
                کالا را در {{ $siteName }} انتخاب کنید و در checkout یکی از طرح‌های زیر را بزنید.
                اعداد سقف و اقساط راهنمای معرفی است؛ مقدار نهایی طبق اعتبارسنجی هر تأمین‌کننده تعیین می‌شود.
            </p>
            <div class="how-to-buy__steps-strip" aria-label="مراحل کوتاه">
                <span class="how-to-buy__step-chip">۱ — ورود</span>
                <span class="how-to-buy__step-chip">۲ — سبد خرید</span>
                <span class="how-to-buy__step-chip">۳ — انتخاب طرح</span>
                <span class="how-to-buy__step-chip">۴ — تحویل</span>
            </div>
        </div>
    </header>

    <section class="how-to-buy__compare" id="payment-plans">
        <div class="htb-wrap">
            <div class="how-to-buy__compare-title">
                <h2>مقایسهٔ طرح‌های پرداخت اعتباری</h2>
                <p>ستون‌ها مطابق جدول مرجع؛ فقط طرح‌های فعال در checkout نمایش داده می‌شوند.</p>
            </div>

            <div class="how-to-buy__table-shell">
                <div class="how-to-buy__table-scroll" role="region" aria-label="جدول طرح‌های اقساطی" tabindex="0">
                    <table class="how-to-buy__table">
                        <thead>
                            <tr>
                                <th scope="col">نام طرح / تأمین‌کننده</th>
                                <th scope="col">سقف وام</th>
                                <th scope="col">دسته‌بندی کالای مجاز</th>
                                <th scope="col">تعداد اقساط</th>
                                <th scope="col">نوع ضمانت</th>
                                <th scope="col">رتبه اعتباری</th>
                                <th scope="col">شرایط خاص</th>
                                <th scope="col">نحوهٔ خرید</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comparisonRows as $row)
                                <tr>
                                    <td>
                                        <div class="how-to-buy__provider">
                                            <span class="how-to-buy__provider-icon" aria-hidden="true">
                                                @if (! empty($row['icon']))
                                                    <img src="{{ $row['icon'] }}" alt="">
                                                @else
                                                    <span class="how-to-buy__provider-fallback">{{ mb_substr($row['label'], 0, 1) }}</span>
                                                @endif
                                            </span>
                                            <span class="how-to-buy__provider-name">{{ $row['label'] }}</span>
                                        </div>
                                    </td>
                                    <td><span class="how-to-buy__ceil">{{ $row['ceil'] }}</span></td>
                                    <td><span class="how-to-buy__categories">{{ $row['categories'] }}</span></td>
                                    <td>{{ $row['installments'] }}</td>
                                    <td>{{ $row['guarantee'] }}</td>
                                    <td>
                                        <div class="how-to-buy__grades" aria-label="رتبه‌های مجاز">
                                            @foreach ($row['credit_grades'] as $grade)
                                                <span class="how-to-buy__grade how-to-buy__grade--{{ $grade }}">{{ $grade }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td><span class="how-to-buy__special">{{ $row['special'] }}</span></td>
                                    <td>
                                        <a class="how-to-buy__buy-btn" href="{{ $row['buy_url'] }}">
                                            نحوهٔ خرید با {{ $row['label'] }}؟
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="how-to-buy__note">
                سقف‌ها و اقساط جدول بالا برای آشنایی با طرح‌ها در {{ $siteName }} است و جایگزین تأیید بانک، کیف پول یا اپ هر سرویس نیست.
            </p>
        </div>
    </section>

    <section class="how-to-buy__end" aria-label="ادامهٔ خرید">
        <div class="htb-wrap how-to-buy__end-inner">
            <p class="how-to-buy__end-lead">آمادهٔ خرید هستید؟ محصول را انتخاب کنید و در checkout طرح اعتباری را فعال کنید.</p>
            <div class="how-to-buy__end-actions">
                <a class="how-to-buy__end-btn how-to-buy__end-btn--primary" href="{{ route('products.index') }}">رفتن به فروشگاه</a>
                <a class="how-to-buy__end-btn how-to-buy__end-btn--ghost" href="{{ route('plans.index') }}">مشاهدهٔ سایر طرح‌ها</a>
            </div>
        </div>
    </section>
</div>
@endsection
