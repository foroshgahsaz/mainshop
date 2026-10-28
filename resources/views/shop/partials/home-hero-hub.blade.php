@php
    $heroHub = $hero_hub ?? ['sidebar_title' => '', 'sidebar_links' => [], 'quick_families' => []];
    $gatewayPlans = $homepage_gateway_plans ?? [];
    $sidebarLinks = $heroHub['sidebar_links'] ?? [];
    $quickFamilies = $heroHub['quick_families'] ?? [];
@endphp

<section class="home-hero-hub max-w-site mx-auto px-4 mt-2 md:mt-4" aria-label="بنر و دسترسی سریع">
    <div class="home-hero-hub__shell">
        <div class="home-hero-hub__grid">
            <div class="home-hero-hub__slider">
                <div class="swiper homeHeroSwiper relative" aria-label="اسلایدر بنر">
                    <div class="swiper-wrapper">
                        @forelse($sliders as $i => $slider)
                            <div class="swiper-slide">
                                @if($slider->link)
                                    <a href="{{ $slider->link }}" class="home-hero-hub__slide-link">
                                        @include('shop.partials.hero-slide-media', ['slider' => $slider, 'isFirst' => $i === 0])
                                    </a>
                                @else
                                    <div class="home-hero-hub__slide-link">
                                        @include('shop.partials.hero-slide-media', ['slider' => $slider, 'isFirst' => $i === 0])
                                    </div>
                                @endif
                            </div>
                        @empty
                            @foreach(['slide-ai.svg', 'slide-discount.svg'] as $i => $hero)
                                <div class="swiper-slide">
                                    <a href="{{ route('products.index') }}" class="home-hero-hub__slide-link">
                                        <img src="{{ asset('shop/images/hero/'.$hero) }}" alt="بنر" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false">
                                    </a>
                                </div>
                            @endforeach
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>

            <aside class="home-hero-hub__aside" aria-label="{{ $heroHub['sidebar_title'] ?? 'دسته‌بندی' }}">
                @if ($sidebarLinks !== [])
                    <ul class="home-hero-hub__brand-list">
                        @foreach ($sidebarLinks as $link)
                            <li>
                                <a href="{{ $link['url'] }}" class="home-hero-hub__brand-link">
                                    <span>{{ $link['label'] }}</span>
                                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="home-hero-hub__empty-hint">
                        لینک‌های «{{ $heroHub['sidebar_title'] }}» هنوز ثبت نشده.
                        ادمین → <strong>تنظیمات → صفحه اصلی فروشگاه</strong> (لینک ویرایش) یا
                        <strong>فروشگاه → خانواده محصول → ویرایش {{ $heroHub['sidebar_title'] }}</strong>
                        → «لینک‌های کنار اسلایدر».
                    </p>
                @endif

                @if ($quickFamilies !== [])
                    <div class="home-hero-hub__quick-families">
                        @foreach ($quickFamilies as $family)
                            <a href="{{ $family['url'] }}" class="home-hero-hub__quick-family">
                                <span class="home-hero-hub__quick-family-image">
                                    <img src="{{ \App\Support\ShopFormatter::productFamilyImage($family['image']) }}" alt="{{ $family['name'] }}" loading="lazy">
                                </span>
                                <span class="home-hero-hub__quick-family-label">{{ $family['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </div>

    @if ($gatewayPlans !== [])
        <div class="home-hero-hub__gateways" aria-label="طرح‌های خرید اعتباری">
            @foreach ($gatewayPlans as $plan)
                <article class="home-gateway-plan-card">
                    <div class="home-gateway-plan-card__logo">
                        @if ($plan['icon'])
                            <img src="{{ $plan['icon'] }}" alt="{{ $plan['label'] }}" loading="lazy">
                        @else
                            <span class="home-gateway-plan-card__logo-fallback">{{ mb_substr($plan['label'], 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="home-gateway-plan-card__body">
                        <h3 class="home-gateway-plan-card__title">{{ $plan['label'] }}</h3>
                        <p class="home-gateway-plan-card__intro">{{ $plan['intro'] }}</p>
                        <a href="{{ $plan['plan_url'] }}" class="home-gateway-plan-card__cta">
                            <span>مشاهده طرح</span>
                            <i class="fas fa-arrow-left-long" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
