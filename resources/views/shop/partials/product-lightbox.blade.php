@php
    use App\Support\ShopFormatter;

    $lightboxImages = $images->isNotEmpty()
        ? $images->map(fn ($img) => ShopFormatter::storageImageUrl($img->image))
        : collect([$mainImage]);
@endphp

<div id="productZoomModal" class="product-lightbox" aria-hidden="true">
    <button type="button" class="product-lightbox__close" aria-label="بستن">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
    </button>
    <button type="button" class="product-lightbox__nav product-lightbox__nav--prev" data-lightbox-prev aria-label="تصویر قبلی">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="m15 18-6-6 6-6"/>
        </svg>
    </button>
    <button type="button" class="product-lightbox__nav product-lightbox__nav--next" data-lightbox-next aria-label="تصویر بعدی">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="m9 18 6-6-6-6"/>
        </svg>
    </button>
    <div class="product-lightbox__stage">
        <div class="swiper productLightboxSwiper">
            <div class="swiper-wrapper">
                @foreach($lightboxImages as $index => $url)
                    <div class="swiper-slide">
                        <img src="{{ $url }}" alt="نمای {{ $index + 1 }} — {{ $product->name }}" loading="lazy">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="product-lightbox__pagination"></div>
</div>
