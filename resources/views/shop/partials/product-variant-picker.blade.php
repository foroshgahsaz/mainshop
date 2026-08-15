@php
    $matrix = \App\Support\ProductVariantMatrix::for($product);
@endphp

@if (! empty($matrix['attributes']))
    <div class="product-info__section" id="productVariantPicker" data-product-variant-picker>
        <p class="product-option-label">انتخاب واریانت</p>

        @foreach ($matrix['attributes'] as $attribute)
            <div class="mb-3" data-variant-attribute="{{ $attribute['id'] }}">
                <p class="text-xs text-gray-500 mb-2 font-medium">{{ $attribute['name'] }}</p>
                <div class="product-option-pills flex flex-wrap gap-2">
                    @foreach ($attribute['values'] as $value)
                        <button type="button"
                                data-variant-value="{{ $value['id'] }}"
                                data-variant-attribute-id="{{ $attribute['id'] }}"
                                class="product-storage-btn">
                            {{ $value['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <script type="application/json" id="productVariantMatrix">@json($matrix)</script>
@endif
