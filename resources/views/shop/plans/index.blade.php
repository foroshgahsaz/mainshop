@extends('layouts.shop')

@section('title', 'طرح‌های خرید اعتباری | '.site_name())

@php
    $seoDescription = 'مقایسه و انتخاب طرح‌های خرید اعتباری و اقساطی '.site_name().'؛ جزئیات هر طرح و لینک ثبت‌نام یا پرداخت.';
    $canonicalUrl = route('plans.index');
@endphp

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
        'title' => 'طرح‌های خرید اعتباری | '.site_name(),
        'description' => $seoDescription,
        'url' => $canonicalUrl,
        'type' => 'website',
    ])" />
@endsection

@section('body_class', 'plans-index-page')

@section('content')
    <div class="plans-index">
        <header class="plans-index__hero">
            <div class="max-w-site mx-auto px-4 plans-index__hero-inner">
                <span class="plans-index__eyebrow">پرداخت اعتباری</span>
                <h1 class="plans-index__title">طرح‌های خرید از {{ site_name() }}</h1>
                <p class="plans-index__lead">
                    {{ $planCount }} طرح فعال برای خرید راحت‌تر؛ هر کارت را باز کنید تا شرایط، سقف اعتبار و مراحل پرداخت را ببینید.
                </p>
                <div class="plans-index__chips" aria-hidden="true">
                    <span class="plans-index__chip">انتخاب در checkout</span>
                    <span class="plans-index__chip">شفاف و سریع</span>
                </div>
            </div>
        </header>

        <div class="max-w-site mx-auto px-4 pb-12 md:pb-16">
            <div class="plans-index__grid" role="list">
                @foreach ($plans as $index => $plan)
                    <a
                        href="{{ $plan['plan_url'] }}"
                        class="home-gateway-plan-card home-gateway-plan-card--listing home-gateway-plan-card--plans-index"
                        role="listitem"
                    >
                        <div class="home-gateway-plan-card__body">
                            <span class="home-gateway-plan-card__title-accent" aria-hidden="true"></span>
                            <h2 class="home-gateway-plan-card__title">{{ $plan['label'] }}</h2>
                            <p class="home-gateway-plan-card__intro">{{ $plan['intro'] }}</p>
                            <span class="home-gateway-plan-card__cta home-gateway-plan-card__cta--static">
                                <span class="home-gateway-plan-card__cta-arrow" aria-hidden="true">←</span>
                                <span>مشاهده طرح</span>
                            </span>
                        </div>
                        <div class="home-gateway-plan-card__logo">
                            @if ($plan['icon'])
                                <img
                                    src="{{ $plan['icon'] }}"
                                    alt=""
                                    @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                    decoding="async"
                                    width="120"
                                    height="110"
                                >
                            @else
                                <span class="home-gateway-plan-card__logo-fallback">{{ mb_substr($plan['label'], 0, 1) }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>

            <p class="plans-index__footnote">
                پس از انتخاب کالا در سبد، در مرحله پرداخت می‌توانید یکی از درگاه‌های فعال را انتخاب کنید.
            </p>
        </div>
    </div>
@endsection
