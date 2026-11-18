@php
    $banner = app(\App\Services\Settings\SettingsService::class)->siteTopBanner();
@endphp
@if ($banner['enabled'] && ($banner['show_desktop'] || $banner['show_mobile']))
    <div class="shop-top-banner" role="region" aria-label="بنر بالای سایت">
        @if ($banner['show_desktop'])
            @php($desk = $banner['desktop'])
            <div class="shop-top-banner__viewport shop-top-banner__viewport--desktop">
                @if ($desk['link'])
                    <a href="{{ $desk['link'] }}" class="shop-top-banner__link" @if (! str_starts_with($desk['link'], '/')) rel="noopener" @endif>
                        <img src="{{ $desk['image_url'] }}"
                             alt="{{ $desk['alt'] }}"
                             class="shop-top-banner__img"
                             width="1920"
                             height="60"
                             decoding="async"
                             fetchpriority="high">
                    </a>
                @else
                    <img src="{{ $desk['image_url'] }}"
                         alt="{{ $desk['alt'] }}"
                         class="shop-top-banner__img"
                         width="1920"
                         height="60"
                         decoding="async"
                         fetchpriority="high">
                @endif
            </div>
        @endif
        @if ($banner['show_mobile'])
            @php($mob = $banner['mobile'])
            <div class="shop-top-banner__viewport shop-top-banner__viewport--mobile">
                @if ($mob['link'])
                    <a href="{{ $mob['link'] }}" class="shop-top-banner__link" @if (! str_starts_with($mob['link'], '/')) rel="noopener" @endif>
                        <img src="{{ $mob['image_url'] }}"
                             alt="{{ $mob['alt'] }}"
                             class="shop-top-banner__img"
                             width="768"
                             height="80"
                             decoding="async"
                             fetchpriority="high">
                    </a>
                @else
                    <img src="{{ $mob['image_url'] }}"
                         alt="{{ $mob['alt'] }}"
                         class="shop-top-banner__img"
                         width="768"
                         height="80"
                         decoding="async"
                         fetchpriority="high">
                @endif
            </div>
        @endif
    </div>
@endif