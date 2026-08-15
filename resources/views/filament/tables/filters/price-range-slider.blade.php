<div class="price-range-slider-wrap mb-4" data-price-range-wrap data-max="{{ $max ?? 10000000 }}">
    <label class="block text-sm font-semibold text-gray-700 mb-3">محدوده قیمت</label>
    <div class="price-range-track relative h-2 bg-gray-200 rounded-full mb-4">
        <div class="price-range-fill absolute h-2 bg-[#7239ea] rounded-full" style="right: 0%; left: 0%;"></div>
    </div>
    <div class="grid grid-cols-2 gap-3 mb-2">
        <input type="range" class="price-range-min w-full accent-[#7239ea]" min="0" max="{{ $max ?? 10000000 }}" step="50000" value="0">
        <input type="range" class="price-range-max w-full accent-[#7239ea]" min="0" max="{{ $max ?? 10000000 }}" step="50000" value="{{ $max ?? 10000000 }}">
    </div>
    <div class="flex justify-between text-xs text-gray-500">
        <span class="price-range-min-label">۰ تومان</span>
        <span class="price-range-max-label">{{ number_format($max ?? 10000000) }} تومان</span>
    </div>
</div>
