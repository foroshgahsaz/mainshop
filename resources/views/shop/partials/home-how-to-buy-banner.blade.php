@php
    $howToBuyUrl = route('plans.how-to-buy');
    $bannerDesktop = asset('shop/images/banners/home-how-to-buy.webp');
    $bannerMobilePath = public_path('shop/images/banners/home-how-to-buy-mobile.webp');
    $bannerMobile = file_exists($bannerMobilePath)
        ? asset('shop/images/banners/home-how-to-buy-mobile.webp')
        : $bannerDesktop;
    $bannerAlt = 'چطوری از '.site_name().' قسطی خرید کنیم؟ راهنمای خرید اقساطی — باجت‌پی، دیجی‌پی، دیبا کارت';
@endphp

<section class="home-how-to-buy-banner" aria-label="راهنمای خرید اقساطی">
    <a href="{{ $howToBuyUrl }}" class="home-how-to-buy-banner__link" target="_blank" rel="noopener noreferrer">
        <picture>
            <source
                media="(max-width: 767px)"
                srcset="{{ $bannerMobile }}"
                type="image/webp"
            >
            <img
                src="{{ $bannerDesktop }}"
                alt="{{ $bannerAlt }}"
                width="1920"
                height="400"
                class="home-how-to-buy-banner__img"
                loading="lazy"
                decoding="async"
            >
        </picture>
    </a>
</section>
