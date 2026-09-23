@php
    $itemCount = (int) ($summary['item_count'] ?? $items->count());
@endphp

<div class="shop-page-wrap @if(!$items->isEmpty()) shop-page-wrap--has-mobile-bar @endif">
    <div class="max-w-site mx-auto px-4 py-6 md:py-10">
        <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-brand-green">خانه</a>
            <span>/</span>
            <span class="text-gray-600">سبد خرید</span>
        </nav>

        @if (session('success'))
            <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
        @endif

        @if ($items->isEmpty())
            <div class="cart-invoice cart-invoice--empty">
                <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                    <path d="M3 6h18" /><path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
                <p class="text-gray-600 mb-2 font-medium">سبد خرید شما خالی است</p>
                <p class="text-sm text-gray-400 mb-6">محصولات مورد علاقه‌تان را به سبد اضافه کنید</p>
                <a href="{{ route('products.index') }}" class="shop-btn-primary inline-flex">مشاهده محصولات</a>
            </div>
        @else
            <div class="cart-invoice">
                <header class="cart-invoice__head">
                    <div>
                        <h1 class="cart-invoice__title">فاکتور سبد خرید</h1>
                        <p class="cart-invoice__meta">{{ $itemCount }} قلم کالا</p>
                    </div>
                    <a href="{{ route('products.index') }}" class="cart-invoice__continue">ادامه خرید</a>
                </header>

                <div class="cart-invoice__table-wrap">
                    <table class="cart-invoice__table">
                        <thead>
                            <tr>
                                <th class="cart-invoice__col-index">ردیف</th>
                                <th class="cart-invoice__col-item">شرح کالا</th>
                                <th>قیمت واحد</th>
                                <th>تعداد</th>
                                <th>مبلغ</th>
                                <th class="cart-invoice__col-action"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php $variantArg = ($item['product_variant_id'] ?? null) !== null ? $item['product_variant_id'] : 'null'; @endphp
                                <tr wire:key="cart-page-{{ $item['product_id'] }}-{{ $item['product_variant_id'] ?? 0 }}">
                                    <td class="cart-invoice__col-index" data-label="ردیف">{{ $loop->iteration }}</td>
                                    <td class="cart-invoice__product" data-label="شرح کالا">
                                        <a href="{{ $item['url'] }}" class="cart-invoice__product-link">
                                            <img src="{{ $item['image'] }}" alt="{{ $item['product_name'] }}">
                                            <span>
                                                <strong>{{ $item['product_name'] }}</strong>
                                                @if (! empty($item['sku']))
                                                    <small>کد: {{ $item['sku'] }}</small>
                                                @endif
                                            </span>
                                        </a>
                                    </td>
                                    <td data-label="قیمت واحد">{{ number_format($item['price']) }} <span>تومان</span></td>
                                    <td data-label="تعداد">
                                        <div class="shop-qty-control">
                                            <button type="button"
                                                    wire:click="decrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                                    class="shop-qty-btn">−</button>
                                            <span class="shop-qty-value">{{ $item['quantity'] }}</span>
                                            <button type="button"
                                                    wire:click="incrementQuantity({{ $item['product_id'] }}, {{ $variantArg }})"
                                                    class="shop-qty-btn">+</button>
                                        </div>
                                    </td>
                                    <td class="cart-invoice__line-total" data-label="مبلغ">
                                        {{ number_format($item['price'] * $item['quantity']) }} <span>تومان</span>
                                    </td>
                                    <td class="cart-invoice__col-action">
                                        <button type="button"
                                                wire:click="remove({{ $item['product_id'] }}, {{ $variantArg }})"
                                                class="cart-invoice__remove">
                                            حذف
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="cart-invoice__totals">
                    <h2>خلاصه فاکتور</h2>
                    <dl>
                        <div>
                            <dt>جمع کالاها ({{ $itemCount }} قلم)</dt>
                            <dd>{{ number_format($summary['subtotal']) }} تومان</dd>
                        </div>
                        @if ($summary['discount'] > 0)
                            <div class="is-discount">
                                <dt>تخفیف</dt>
                                <dd>− {{ number_format($summary['discount']) }} تومان</dd>
                            </div>
                        @endif
                        <div>
                            <dt>
                                هزینه ارسال
                                @if ($summary['shipping_method'])
                                    <small>({{ $summary['shipping_method'] }})</small>
                                @endif
                            </dt>
                            <dd>
                                @if ($summary['shipping'] === 0)
                                    <span class="is-free">رایگان</span>
                                @else
                                    {{ number_format($summary['shipping']) }} تومان
                                @endif
                            </dd>
                        </div>
                        <div class="is-payable">
                            <dt>مبلغ قابل پرداخت</dt>
                            <dd>{{ number_format($summary['total']) }} تومان</dd>
                        </div>
                    </dl>
                    <a href="{{ route('checkout') }}" class="shop-btn-gold cart-invoice__checkout">
                        ادامه و تسویه حساب
                    </a>
                </div>
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
