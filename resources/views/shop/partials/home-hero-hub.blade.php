@php
    $heroHub = $hero_hub ?? ['sidebar_title' => '', 'sidebar_links' => [], 'quick_families' => []];
    $planWidget = $homepage_plan_widget ?? ['cards' => [], 'show_continue' => false, 'continue_url' => route('plans.index')];
    $planCards = $planWidget['cards'] ?? [];
    $showPlansContinue = (bool) ($planWidget['show_continue'] ?? false);
    $plansContinueUrl = $planWidget['continue_url'] ?? route('plans.index');
    $sidebarLinks = $heroHub['sidebar_links'] ?? [];
    $quickFamilies = $heroHub['quick_families'] ?? [];
@endphp

<section class="home-hero-hub" aria-label="بنر و دسترسی سریع">
    <div class="home-hero-hub__inner max-w-site mx-auto px-4 mt-2 md:mt-4">
        <div class="home-hero-hub__shell" aria-label="بنر و دسته‌بندی‌ها">
            <div class="home-hero-hub__promo">
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

            <aside class="home-hero-hub__sidebar" aria-label="{{ $heroHub['sidebar_title'] ?? 'دسته‌بندی' }}">
                @if ($sidebarLinks !== [])
                    <nav class="home-hero-hub__nav-list" aria-label="دسته‌بندی اصلی">
                        @foreach ($sidebarLinks as $link)
                            <a href="{{ $link['url'] }}" class="home-hero-hub__nav-item">
                                <span class="home-hero-hub__nav-label">{{ $link['label'] }}</span>
                                @if (! empty($link['image']))
                                    <img
                                        src="{{ $link['image'] }}"
                                        alt=""
                                        class="home-hero-hub__nav-icon-img"
                                        width="22"
                                        height="22"
                                        loading="lazy"
                                    >
                                @else
                                    <span class="home-hero-hub__nav-icon-fallback" aria-hidden="true"></span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                @else
                    <p class="home-hero-hub__empty-hint">
                        لینک‌های «{{ $heroHub['sidebar_title'] }}» هنوز ثبت نشده.
                        ادمین → <strong>تنظیمات → صفحه اصلی فروشگاه</strong> یا
                        <strong>فروشگاه → خانواده محصول → ویرایش {{ $heroHub['sidebar_title'] }}</strong>
                        → «لینک‌های کنار اسلایدر».
                    </p>
                @endif

                @if ($quickFamilies !== [])
                    <div class="home-hero-hub__category-list">
                        @foreach ($quickFamilies as $family)
                            <a href="{{ $family['url'] }}" class="home-hero-hub__category">
                                <span class="home-hero-hub__category-icon">
                                    <img src="{{ \App\Support\ShopFormatter::productFamilyImage($family['image']) }}" alt="" loading="lazy">
                                </span>
                                <span class="home-hero-hub__category-label">{{ $family['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>

        @if ($planCards !== [] || $showPlansContinue)
            <div class="home-hero-hub__cards" aria-label="طرح‌های خرید اعتباری">
                @foreach ($planCards as $plan)
                    <article class="home-gateway-plan-card">
                        <div class="home-gateway-plan-card__body">
                            <span class="home-gateway-plan-card__title-accent" aria-hidden="true"></span>
                            <h3 class="home-gateway-plan-card__title">{{ $plan['label'] }}</h3>
                            <p class="home-gateway-plan-card__intro">{{ $plan['intro'] }}</p>
                            <a href="{{ $plan['plan_url'] }}" class="home-gateway-plan-card__cta">
                                <span class="home-gateway-plan-card__cta-arrow" aria-hidden="true">←</span>
                                <span>مشاهده طرح</span>
                            </a>
                        </div>
                        <div class="home-gateway-plan-card__logo">
                            @if ($plan['icon'])
                                <img src="{{ $plan['icon'] }}" alt="{{ $plan['label'] }}" loading="lazy">
                            @else
                                <span class="home-gateway-plan-card__logo-fallback">{{ mb_substr($plan['label'], 0, 1) }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach

                @if ($showPlansContinue)
                    <a href="{{ $plansContinueUrl }}" class="home-gateway-plan-card home-gateway-plan-card--continue" aria-label="مشاهده همه طرح‌ها">
                        <div class="home-gateway-plan-card__body home-gateway-plan-card__body--center">
                            <span class="home-gateway-plan-card__title-accent" aria-hidden="true"></span>
                            <h3 class="home-gateway-plan-card__title">مشاهده همه</h3>
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
                @endif
            </div>
        @endif
    </div>
</section>
