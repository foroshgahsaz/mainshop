<div>

    @if (session('success'))

        <div class="mb-2 p-2 bg-emerald-50 text-emerald-700 rounded text-sm">{{ session('success') }}</div>

    @endif

    @if (session('error'))

        <div class="mb-2 p-2 bg-red-50 text-red-700 rounded text-sm">{{ session('error') }}</div>

    @endif



    @if ($selectedLabel)

        <div class="mb-3 p-3 bg-gray-50 rounded-xl border border-gray-100">

            <p class="text-[11px] text-gray-400 mb-1">انتخاب شما</p>

            <p class="text-sm font-bold text-gray-800 leading-relaxed">{{ $selectedLabel }}</p>

        </div>

    @endif



    @if ($selectedPrice !== null)

        <div class="py-3 border-t border-b border-gray-100 mb-4">

            @if ($comparePrice)

                <p class="text-sm text-gray-400 line-through mb-1">{{ number_format($comparePrice) }} تومان</p>

            @endif

            <p class="product-buy-box__price">

                {{ number_format($selectedPrice) }} <span>تومان</span>

            </p>

        </div>

    @endif



    @if ($selectedStock !== null)

        <p class="text-xs text-gray-500 mb-3">

            موجودی:

            <strong class="{{ $selectedStock > 0 ? 'text-emerald-600' : 'text-red-600' }}">

                {{ $selectedStock > 0 ? 'موجود' : 'ناموجود' }}

            </strong>

        </p>

    @endif



    <div class="flex items-center gap-3 flex-wrap">

        <div class="product-qty">

            <button type="button" wire:click="decrementQuantity" aria-label="کم کردن">−</button>

            <span class="product-qty__value" aria-live="polite">{{ $quantity }}</span>

            <button type="button" wire:click="incrementQuantity" aria-label="زیاد کردن">+</button>

        </div>

        <button type="button"

                wire:click="add"

                wire:loading.attr="disabled"

                wire:target="add"

                @disabled($selectedStock === 0)

                class="product-add-cart flex-1 min-w-[200px] @if($selectedStock === 0) opacity-50 cursor-not-allowed @endif">

            <span wire:loading.remove wire:target="add">افزودن به سبد خرید</span>

            <span wire:loading wire:target="add">در حال افزودن...</span>

        </button>

    </div>

</div>


