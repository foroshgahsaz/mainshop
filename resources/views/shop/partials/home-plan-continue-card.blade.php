@php
    $plansContinueUrl = $plansContinueUrl ?? route('plans.index');
    $headingTag = $headingTag ?? 'h3';
@endphp
<a href="{{ $plansContinueUrl }}" class="home-gateway-plan-card home-gateway-plan-card--continue" aria-label="مشاهده همه طرح‌ها">
    <div class="home-gateway-plan-card__body home-gateway-plan-card__body--center">
        <span class="home-gateway-plan-card__title-accent" aria-hidden="true"></span>
        <{{ $headingTag }} class="home-gateway-plan-card__title">مشاهده همه</{{ $headingTag }}>
        <p class="home-gateway-plan-card__intro">همه طرح‌های خرید اعتباری فروشگاه</p>
        <span class="home-gateway-plan-card__cta home-gateway-plan-card__cta--static">
            <span class="home-gateway-plan-card__cta-arrow" aria-hidden="true">←</span>
            <span>رفتن به لیست</span>
        </span>
    </div>
    <div class="home-gateway-plan-card__logo home-gateway-plan-card__logo--continue" aria-hidden="true">
        <span class="home-gateway-plan-card__logo-fallback">+</span>
    </div>
</a>
