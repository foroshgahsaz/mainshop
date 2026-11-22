<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteName }} | جمعه سیاه</title>
    <meta name="description" content="{{ $page['hero_intro'] ?? '' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;700;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/blackfriday-neon.css') }}?v=2026-11-22-mobile-promo">
    <style>
        #products { scroll-margin-top: 32px; }
        .btn-neon { cursor: pointer; font: inherit; }
        html { scroll-behavior: smooth; }
    </style>
</head>
<body>
<div class="glow-orb orb1"></div>
<div class="glow-orb orb2"></div>
<div class="glow-orb orb3"></div>

<div class="container" id="store">
    <div class="top-bar">
        @if ($headerLogoUrl)
            <img alt="لوگوی {{ $siteName }}" class="header-logo" src="{{ $headerLogoUrl }}" width="270" height="78">
        @endif
        <nav aria-label="منوی اصلی سایت" class="site-nav">
            @foreach ($page['nav_links'] ?? [] as $link)
                <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </div>

    <section class="hero">
        @if (filled($page['badge_text'] ?? null))
            <div class="badge">{{ $page['badge_text'] }}</div>
        @endif
        @if ($heroLogoUrl)
            <img alt="لوگوی {{ $siteName }}" class="brand-logo" id="chinibazar" src="{{ $heroLogoUrl }}" width="680" height="220">
        @endif
        @if (filled($page['hero_intro'] ?? null))
            <p>{{ $page['hero_intro'] }}</p>
        @endif
        <button type="button" class="btn-neon" id="blackfriday-cta" data-scroll-target="products">
            {{ $page['cta_label'] ?? 'مشاهده تخفیف' }}
            <span aria-hidden="true" class="cta-rocket">🚀</span>
        </button>
    </section>

    <h2 class="section-title" id="products-heading">{{ $page['section_title'] ?? 'تخفیفات ویژه' }}</h2>
    <div class="products promo-products" id="products">
        @foreach ($promos as $promo)
            <a
                aria-label="{{ $promo['label'] }}"
                class="promo-product"
                href="{{ $promo['url'] }}"
                rel="noopener"
                @if (str_starts_with($promo['url'], 'http')) target="_blank" @endif
            >
                <img
                    alt="{{ $promo['label'] }}"
                    class="promo-image"
                    loading="lazy"
                    src="{{ $promo['image_url'] }}"
                    style="object-fit: contain;"
                >
            </a>
        @endforeach
    </div>

  @php
      $marqueeItems = $page['marquee_items'] ?? [];
      $marqueeLoop = count($marqueeItems) > 0 ? array_merge($marqueeItems, $marqueeItems, $marqueeItems) : [];
  @endphp
    @if ($marqueeLoop !== [])
        <div class="marquee">
            <div class="marquee-track">
                @foreach ($marqueeLoop as $item)
                    <span>{{ is_array($item) ? ($item['text'] ?? '') : $item }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <footer>
        {{ $page['footer_text'] ?? '' }}
    </footer>
</div>

<script>
    (function () {
        var cta = document.getElementById('blackfriday-cta');
        var target = document.getElementById('products');
        if (!cta || !target) return;
        cta.addEventListener('click', function (e) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#products');
            }
        });
        document.querySelectorAll('.site-nav a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (ev) {
                var id = (anchor.getAttribute('href') || '').slice(1);
                if (!id) return;
                var el = document.getElementById(id);
                if (!el) return;
                ev.preventDefault();
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    })();
</script>
</body>
</html>