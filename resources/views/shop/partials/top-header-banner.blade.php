@php
    $banner = app(\App\Services\Settings\SettingsService::class)->siteTopBanner();
@endphp
@if ($banner['enabled'] && $banner['image_url'])
    <div class="shop-top-banner" role="region" aria-label="بنر بالای سایت">
        @if ($banner['link'])
            <a href="{{ $banner['link'] }}" class="shop-top-banner__link" @if (! str_starts_with($banner['link'], '/')) rel="noopener" @endif>
                <img src="{{ $banner['image_url'] }}"
                     alt="{{ $banner['alt'] }}"
                     class="shop-top-banner__img"
                     width="1920"
                     height="60"
                     decoding="async"
                     fetchpriority="high">
            </a>
        @else
            <img src="{{ $banner['image_url'] }}"
                 alt="{{ $banner['alt'] }}"
                 class="shop-top-banner__img"
                 width="1920"
                 height="60"
                 decoding="async"
                 fetchpriority="high">
        @endif
    </div>
@endif