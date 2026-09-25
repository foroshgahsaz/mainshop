@props(['product', 'variant' => 'clothing', 'section' => null])

@php
    use App\Support\ShopFormatter;
    $discount = ShopFormatter::discountPercent($product);
    $comparePrice = ShopFormatter::comparePrice($product);
    $image = $section
        ? ShopFormatter::productImageForSection($product, $section)
        : ShopFormatter::productImage($product);
@endphp

<a href="{{ route('products.show', $product) }}" class="product-card product-card-link {{ $variant === 'clothing' ? 'clothing-card' : 'product-scroll-card' }} h-full w-full">
    <div class="product-card__media @if($variant !== 'clothing') p-4 @endif">
        <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy" @if($variant !== 'clothing') class="h-28 object-contain mx-auto" @endif>
    </div>
    @if($variant === 'clothing')
        <div class="clothing-card__body">
            <div class="product-card__brand">
                <span class="product-card__brand-icon" aria-hidden="true"></span>
                <span>{{ $product->category?->name ?? site_name() }}</span>
            </div>
            <h4 class="clothing-card__title">{{ $product->name }}</h4>
            <div class="product-card__price-row">
                @if($discount)<span class="product-card__discount">{{ $discount }}٪</span>@endif
                <div class="product-card__price-group">
                    <x-shop.price
                        :amount="$product->effective_price"
                        :compare="$comparePrice"
                        inline
                    />
                </div>
            </div>
        </div>
    @else
        <div class="p-3 flex flex-col gap-2 flex-1">
            <h4 class="text-xs font-bold line-clamp-2 leading-6">{{ $product->name }}</h4>
            <div class="flex items-center justify-end mt-auto">
                <x-shop.price
                    :amount="$product->effective_price"
                    :compare="$comparePrice"
                    class="product-card__price items-start text-left mt-2.5"
                />
            </div>
        </div>
    @endif
</a>
