@php
    $plan = $plan ?? [];
    $headingTag = $headingTag ?? 'h3';
@endphp
<article class="home-gateway-plan-card">
    <div class="home-gateway-plan-card__body">
        <span class="home-gateway-plan-card__title-accent" aria-hidden="true"></span>
        <{{ $headingTag }} class="home-gateway-plan-card__title">{{ $plan['label'] }}</{{ $headingTag }}>
        <p class="home-gateway-plan-card__intro">{{ $plan['intro'] }}</p>
        <a href="{{ $plan['plan_url'] }}" class="home-gateway-plan-card__cta">
            <span class="home-gateway-plan-card__cta-arrow" aria-hidden="true">←</span>
            <span>مشاهده طرح</span>
        </a>
    </div>
    <div class="home-gateway-plan-card__logo">
        @if ($plan['icon'] ?? null)
            <img src="{{ $plan['icon'] }}" alt="{{ $plan['label'] }}" loading="lazy">
        @else
            <span class="home-gateway-plan-card__logo-fallback">{{ mb_substr($plan['label'], 0, 1) }}</span>
        @endif
    </div>
</article>
