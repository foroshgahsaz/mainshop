@props(['product', 'section' => null])

@php
    use App\Support\ShopFormatter;
    $discount = ShopFormatter::discountPercent($product);
    $comparePrice = ShopFormatter::comparePrice($product);
    $image = $section
        ? ShopFormatter::productImageForSection($product, $section)
        : ShopFormatter::productImage($product);
@endphp

<article class="deal-card">
    <a href="{{ route('products.show', $product) }}" class="deal-card__link">
        <div class="deal-card__media">
            @if($discount)
                <span class="deal-card__discount">{{ $discount }} ٪</span>
            @endif
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" width="320" height="320">
        </div>
        <h4 class="deal-card__title">{{ $product->name }}</h4>
        <x-shop.price
            :amount="$product->effective_price"
            :compare="$comparePrice"
            class="deal-card__pricing items-start"
        />
    </a>
</article>
