@php
    $trustBadges = app(\App\Services\Settings\TrustBadgeService::class)->active();
@endphp

@if ($trustBadges !== [])
    <div class="site-footer__trust-col">
        <h4 class="footer-heading hidden md:block">نمادهای اعتماد</h4>
        <div class="site-footer__trust-badges">
            @include('shop.partials.trust-badge-items', ['trustBadges' => $trustBadges])
        </div>
    </div>
@endif
