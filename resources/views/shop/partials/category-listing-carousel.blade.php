@php
    use App\Support\ShopFormatter;

    $items = $categoryCarousel ?? collect();
@endphp

@if(isset($category) && $items->isNotEmpty())
    <section class="listing-category-carousel w-full" aria-label="زیردسته‌ها">
        <div class="swiper categorySwiper listing-category-carousel__swiper w-full">
            <div class="swiper-wrapper">
                @foreach($items as $child)
                    <div class="swiper-slide">
                        <a href="{{ route('categories.show', $child) }}"
                           class="category-card listing-category-carousel__card">
                            <div class="category-card__image">
                                <img src="{{ ShopFormatter::categoryImage($child->image) }}"
                                     alt=""
                                     width="80"
                                     height="80"
                                     loading="lazy">
                            </div>
                            <span class="category-card__label text-center text-xs font-medium text-gray-800">{{ $child->name }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
