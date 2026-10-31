@extends('layouts.shop')

@section('title', 'طرح‌های خرید | '.site_name())

@section('content')
    <div class="max-w-site mx-auto px-4 py-8 md:py-12">
        <header class="section-header mb-8">
            <h1 class="section-title text-xl md:text-2xl">طرح‌های خرید</h1>
            <p class="text-sm text-gray-500 mt-2">لیست طرح‌ها و گزینه‌های پرداخت اعتباری فروشگاه</p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <a href="{{ $plan['plan_url'] }}" class="home-gateway-plan-card home-gateway-plan-card--listing block no-underline text-inherit">
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
                            <img src="{{ $plan['icon'] }}" alt="" loading="lazy">
                        @else
                            <span class="home-gateway-plan-card__logo-fallback">{{ mb_substr($plan['label'], 0, 1) }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
