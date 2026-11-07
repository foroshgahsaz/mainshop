@php
    $howToBuyUrl = route('plans.how-to-buy');
    $bannerDesktop = asset('shop/images/banners/home-how-to-buy.webp');
    $mobileWebp = public_path('shop/images/banners/home-how-to-buy-mobile.webp');
    $mobilePng = public_path('shop/images/banners/home-how-to-buy-mobile.png');
    $bannerMobile = file_exists($mobileWebp)
        ? asset('shop/images/banners/home-how-to-buy-mobile.webp')
        : (file_exists($mobilePng)
            ? asset('shop/images/banners/home-how-to-buy-mobile.png')
            : $bannerDesktop);
    $bannerV = '2026-11-07-h200';
    $bannerDesktop = $bannerDesktop.'?v='.$bannerV;
    $bannerMobile = $bannerMobile.'?v='.$bannerV;
    $bannerAlt = 'چطوری از '.site_name().' قسطی خرید کنیم؟ راهنمای خرید اقساطی — باجت‌پی، کالانو، دیجی‌پی، نئوپای';
@endphp

<section class="home-how-to-buy-banner" aria-label="راهنمای خرید اقساطی">
    <a href="{{ $howToBuyUrl }}" class="home-how-to-buy-banner__link" target="_blank" rel="noopener noreferrer">
        <picture>
            <source media="(max-width: 767px)" srcset="{{ $bannerMobile }}">
            <img
                src="{{ $bannerDesktop }}"
                alt="{{ $bannerAlt }}"
                width="1280"
                height="200"
                sizes="100vw"
                class="home-how-to-buy-banner__img"
                loading="lazy"
                decoding="async"
            >
        </picture>
    </a>
</section>
