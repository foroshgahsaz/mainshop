@php
    $howToBuyUrl = route('plans.how-to-buy');
    $bannerSrc = asset('shop/images/banners/home-how-to-buy.png');
@endphp

<section class="home-how-to-buy-banner" aria-label="راهنمای خرید اقساطی">
    <a href="{{ $howToBuyUrl }}" class="home-how-to-buy-banner__link" target="_blank" rel="noopener noreferrer">
            <img
                src="{{ $bannerSrc }}"
                alt="چطوری از {{ site_name() }} قسطی خرید کنیم؟ راهنمای خرید اقساطی — باجت‌پی، دیجی‌پی، دیبا کارت"
                width="1280"
                height="360"
                class="home-how-to-buy-banner__img"
                loading="lazy"
                decoding="async"
            >
    </a>
</section>
