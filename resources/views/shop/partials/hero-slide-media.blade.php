@php
    use App\Support\ShopFormatter;

    $desktopUrl = ShopFormatter::sectionImage('hero', $slider->image, 'shop/images/hero/slide-ai.svg');
    $mobilePath = filled($slider->image_mobile) ? $slider->image_mobile : $slider->image;
    $mobileUrl = ShopFormatter::sectionImage('hero_mobile', $mobilePath, 'shop/images/hero/slide-ai.svg');
    $alt = $slider->title ?? 'بنر';
    $isFirst = (bool) ($isFirst ?? false);
@endphp
<picture class="hero-slide__picture">
    <source
        media="(max-width: 767px)"
        srcset="{{ $mobileUrl }}"
        width="750"
        height="400"
    >
    <img
        src="{{ $desktopUrl }}"
        alt="{{ $alt }}"
        class="hero-slide__img"
        width="1920"
        height="480"
        decoding="async"
        @if($isFirst) fetchpriority="high" @endif
        loading="{{ $isFirst ? 'eager' : 'lazy' }}"
        draggable="false"
    >
</picture>
