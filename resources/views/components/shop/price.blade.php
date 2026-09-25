@props([
    'amount',
    'compare' => null,
    'inline' => false,
])

@php
    $showCompare = filled($compare) && (int) $compare > (int) $amount;
@endphp

<div {{ $attributes->class([
    'shop-price',
    'shop-price--inline' => $inline,
]) }}>
    @if ($inline)
        <span class="shop-price__current">{{ number_format((int) $amount) }} <span>تومان</span></span>
        @if ($showCompare)
            <span class="shop-price__compare">{{ number_format((int) $compare) }} <span>تومان</span></span>
        @endif
    @else
        @if ($showCompare)
            <span class="shop-price__compare">{{ number_format((int) $compare) }} <span>تومان</span></span>
        @endif
        <span class="shop-price__current">{{ number_format((int) $amount) }} <span>تومان</span></span>
    @endif
</div>
