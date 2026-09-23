@extends('layouts.shop')

@section('title', site_name() . ' | فروشگاه آنلاین')

@section('content')
    @if(session('success'))
        <div class="max-w-site mx-auto px-4 pt-3">
            <div class="bg-emerald-50 text-emerald-700 text-sm px-4 py-2 rounded-xl">{{ session('success') }}</div>
        </div>
    @endif

    {{-- HERO --}}
    <section class="main-slider-outer mt-2 md:mt-4">
        <div class="swiper mainSwiper relative" aria-label="اسلایدر بنر">
            <div class="swiper-wrapper">
                @forelse($sliders as $i => $slider)
                    <div class="swiper-slide">
                        @php
                            $url = \App\Support\ShopFormatter::sectionImage('hero', $slider->image, 'shop/images/hero/slide-ai.svg');
                        @endphp
                        @if($slider->link)
                            <a href="{{ $slider->link }}" class="hero-slide__link">
                                <img src="{{ $url }}" alt="{{ $slider->title ?? 'بنر' }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false">
                            </a>
                        @else
                            <div class="hero-slide__link">
                                <img src="{{ $url }}" alt="{{ $slider->title ?? 'بنر' }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false">
                            </div>
                        @endif
                    </div>
                @empty
                    @foreach(['slide-ai.svg', 'slide-discount.svg', 'slide-python.svg'] as $i => $hero)
                        <div class="swiper-slide">
                            <a href="{{ route('products.index') }}" class="hero-slide__link">
                                <img src="{{ asset('shop/images/hero/'.$hero) }}" alt="بنر" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false">
                            </a>
                        </div>
                    @endforeach
                @endforelse
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    {{-- CATEGORIES --}}
    @if($categories->isNotEmpty())
        <section class="max-w-site mx-auto px-4 featured-categories-section">
            <div class="flex items-center justify-between section-header">
                <h2 class="section-title">دسته‌بندی‌های منتخب</h2>
                <a href="{{ route('products.index') }}" class="section-nav-link">مشاهده همه</a>
            </div>
            <div class="swiper categorySwiper">
                <div class="swiper-wrapper">
                    @foreach($categories as $category)
                        <div class="swiper-slide">
                            <a href="{{ route('categories.show', $category) }}" class="category-card">
                                <span class="category-card__image">
                                    <img src="{{ \App\Support\ShopFormatter::categoryImage($category->image) }}" alt="{{ $category->name }}" loading="lazy">
                                </span>
                                <span>{{ $category->name }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- SPECIAL SALE --}}
    @if($discounted->isNotEmpty())
        <section class="special-sale-section featured-deals-section">
            <div class="max-w-site mx-auto px-4">
                <div class="special-sale-header section-header">
                    <h2 class="section-title">فروش ویژه</h2>
                    <div class="special-sale-header__actions">
                        <a href="{{ route('products.index') }}" class="section-nav-link">مشاهده همه</a>
                        <button type="button" class="special-sale-nav-btn" data-swiper-prev aria-label="قبلی">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button type="button" class="special-sale-nav-btn" data-swiper-next aria-label="بعدی">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M12.5 4.5 7 10l5.5 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="special-sale-slider max-w-site mx-auto">
                <div class="swiper featuredDealsSwiper">
                    <div class="swiper-wrapper">
                        @foreach($discounted as $product)
                            <div class="swiper-slide h-auto">
                                <x-shop.deal-card :product="$product" section="deals" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- NEW PRODUCTS --}}
    @if($new->isNotEmpty())
        <section class="other-products-section">
            <div class="max-w-site mx-auto px-4">
                <div class="flex items-center justify-between section-header">
                    <h2 class="section-title">جدیدترین محصولات</h2>
                    <a href="{{ route('products.index') }}" class="section-nav-link">مشاهده همه</a>
                </div>
            </div>
            <div class="other-products-slider product-slider-overlay relative">
                <div class="swiper productSwiper">
                    <div class="swiper-wrapper">
                        @foreach($new as $product)
                            <div class="swiper-slide h-auto">
                                <x-shop.product-card :product="$product" variant="clothing" section="new_products" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- BEST SELLERS --}}
    @if($best_sellers->isNotEmpty())
        <section class="max-w-site mx-auto px-4 programming-section">
            <div class="flex items-center justify-between section-header">
                <h2 class="section-title">پرفروش‌ترین‌ها</h2>
            </div>
            <div class="product-slider-overlay relative">
                <div class="swiper programmingSwiper">
                    <div class="swiper-wrapper">
                        @foreach($best_sellers as $product)
                            <div class="swiper-slide h-auto">
                                <x-shop.product-card :product="$product" variant="scroll" section="best_sellers" />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- BLOG --}}
    @if($posts->isNotEmpty())
        <section class="articles-section relative">
            <div id="articles-content" class="max-w-site mx-auto px-4 articles-section__inner">
                <div class="flex items-center justify-between section-header">
                    <h2 class="section-title">مجله {{ site_name() }}</h2>
                    <a href="{{ route('blog.index') }}" class="section-nav-link">مشاهده همه</a>
                </div>
                <div class="swiper blogSwiper mb-5">
                    <div class="swiper-wrapper">
                        @foreach($posts as $post)
                            <div class="swiper-slide h-auto">
                                <article class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                                    <a href="{{ route('blog.show', $post) }}">
                                        <div class="relative">
                                            <img src="{{ \App\Support\ShopFormatter::sectionImage('blog', $post->image, 'shop/images/blog/article-1.svg') }}" alt="{{ $post->title }}" class="w-full h-40 object-cover" loading="lazy">
                                        </div>
                                    </a>
                                    <div class="p-4">
                                        <a href="{{ route('blog.show', $post) }}">
                                            <h3 class="text-sm font-bold leading-7 mb-3 hover:text-brand-gold">{{ $post->title }}</h3>
                                        </a>
                                        <p class="text-[11px] text-gray-400">{{ optional($post->published_at)->format('Y/m/d') }}</p>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
