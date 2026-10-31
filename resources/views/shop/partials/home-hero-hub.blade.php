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
            <div class="home-hero-hub__cards-wrap" aria-label="طرح‌های خرید اعتباری">
                <div class="home-hero-hub__cards home-hero-hub__cards--grid">
                    @foreach ($planCards as $plan)
                        @include('shop.partials.home-plan-card', ['plan' => $plan, 'headingTag' => 'h3'])
                    @endforeach
                    @if ($showPlansContinue)
                        @include('shop.partials.home-plan-continue-card', ['plansContinueUrl' => $plansContinueUrl, 'headingTag' => 'h3'])
                    @endif
                </div>
                <div class="swiper homePlansSwiper home-hero-hub__cards-swiper" aria-label="اسلایدر طرح‌های خرید">
                    <div class="swiper-wrapper">
                        @foreach ($planCards as $plan)
                            <div class="swiper-slide">
                                @include('shop.partials.home-plan-card', ['plan' => $plan, 'headingTag' => 'h3'])
                            </div>
                        @endforeach
                        @if ($showPlansContinue)
                            <div class="swiper-slide">
                                @include('shop.partials.home-plan-continue-card', ['plansContinueUrl' => $plansContinueUrl, 'headingTag' => 'h3'])
                            </div>
                        @endif
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        @endif
    </div>
</section>
