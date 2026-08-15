<div class="shop-page-wrap @if(!$items->isEmpty()) shop-page-wrap--has-mobile-bar @endif">
    <div class="max-w-site mx-auto px-4 py-6 md:py-10">
        <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-brand-green">خانه</a>
            <span>/</span>
            <span class="text-gray-600">سبد خرید</span>
        </nav>

        <h1 class="text-xl md:text-2xl font-black text-navy mb-6">سبد خرید</h1>

        @if (session('success'))
            <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
        @endif

        @if ($items->isEmpty())
            <div class="shop-card p-12 text-center">
                <svg class="w-20 h-20 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                    <path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
                <p class="text-gray-600 mb-2 font-medium">سبد خرید شما خالی است</p>
                <p class="text-sm text-gray-400 mb-6">محصولات مورد علاقه‌تان را به سبد اضافه کنید</p>
                <a href="{{ route('products.index') }}" class="shop-btn-primary inline-flex">مشاهده محصولات</a>
            </div>
        @else
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach ($items as $item)
                        <div class="shop-card p-4 flex flex-col sm:flex-row gap-4"
                             wire:key="cart-page-{{ $item['product_id'] }}-{{ $item['product_variant_id'] ?? 0 }}">
                            <a href="{{ $item['url'] }}" class="shrink-0">
                                <img src="{{ $item['image'] }}"
                                     alt="{{ $item['product_name'] }}"
                                     class="w-24 h-24 rounded-xl bg-gray-50 object-contain p-1">
                            </a>
                            <div class="flex-1 min-w-0 flex flex-col justify-between gap-3">
                                <div>
                                    <a href="{{ $item['url'] }}" class="font-bold text-sm md:text-base text-navy hover:text-brand-green line-clamp-2">
                                        {{ $item['product_name'] }}
                                    </a>
                                    @if (! empty($item['sku']))
                                        <p class="text-xs text-gray-400 mt-1">کد: {{ $item['sku'] }}</p>
                                    @endif
                                    <p class="text-brand-green font-black mt-2">{{ number_format($item['price']) }} تومان</p>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="shop-qty-control">
                                        @php $variantArg = ($item['product_variant_id'] ?? null) !== null ? $item['product_variant_id'] : 'null'; @endphp
                                        <button type="button"
                                                wire:click="decrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                                class="shop-qty-btn">−</button>
                                        <span class="shop-qty-value">{{ $item['quantity'] }}</span>
                                        <button type="button"
                                                wire:click="incrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                                class="shop-qty-btn">+</button>
                                    </div>
                                    <button wire:click="remove({{ $item['product_id'] }}, {{ $variantArg }})"
                                            class="text-red-500 text-sm font-medium hover:text-red-700">
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <aside class="lg:w-80 shrink-0">
                    <div class="shop-card p-5 lg:sticky lg:top-24 space-y-4">
                        <h2 class="font-bold text-navy border-b border-gray-100 pb-3">خلاصه سفارش</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>جمع کالاها ({{ $summary['item_count'] }} قلم)</span>
                                <span>{{ number_format($summary['subtotal']) }} تومان</span>
                            </div>
                            @if ($summary['discount'] > 0)
                                <div class="flex justify-between text-emerald-600">
                                    <span>تخفیف</span>
                                    <span>− {{ number_format($summary['discount']) }} تومان</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-gray-600">
                                <span>
                                    هزینه ارسال
                                    @if ($summary['shipping_method'])
                                        <span class="text-[11px] text-gray-400">({{ $summary['shipping_method'] }})</span>
                                    @endif
                                </span>
                                <span>
                                    @if ($summary['shipping'] === 0)
                                        <span class="text-emerald-600 font-medium">رایگان</span>
                                    @else
                                        {{ number_format($summary['shipping']) }} تومان
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                            <span class="font-bold text-navy">مبلغ قابل پرداخت</span>
                            <span class="text-lg font-black text-brand-green">{{ number_format($summary['total']) }} تومان</span>
                        </div>
                        <a href="{{ route('checkout') }}" class="shop-btn-gold block text-center w-full">
                            ادامه و تسویه حساب
                        </a>
                        <a href="{{ route('products.index') }}" class="block text-center text-xs text-gray-500 hover:text-brand-green py-1">
                            ادامه خرید
                        </a>
                    </div>
                </aside>
            </div>

            <div class="shop-mobile-bar lg:hidden" aria-label="خلاصه سبد خرید">
                <div class="shop-mobile-bar__info">
                    <span class="shop-mobile-bar__label">مبلغ قابل پرداخت</span>
                    <span class="shop-mobile-bar__price">{{ number_format($summary['total']) }} تومان</span>
                </div>
                <a href="{{ route('checkout') }}" class="shop-mobile-bar__btn">تسویه حساب</a>
            </div>
        @endif
    </div>
</div>
