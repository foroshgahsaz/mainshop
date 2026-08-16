<div class="shop-page-wrap @if($preview) shop-page-wrap--has-mobile-bar @endif">
    <div class="max-w-site mx-auto px-4 py-6 md:py-10">
        <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-brand-green">خانه</a>
            <span>/</span>
            <a href="{{ route('cart') }}" class="hover:text-brand-green">سبد خرید</a>
            <span>/</span>
            <span class="text-gray-600">تسویه حساب</span>
        </nav>

        <h1 class="text-xl md:text-2xl font-black text-navy mb-6">تسویه حساب</h1>

        @if ($error)
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ $error }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
        @endif

        @if ($preview)
            <form wire:submit="placeOrder" id="checkoutForm" class="flex flex-col lg:flex-row gap-6 lg:gap-8">
                <div class="flex-1 space-y-5">
                    <section class="shop-card p-5">
                        <h2 class="shop-section-title">انتخاب آدرس</h2>
                        @forelse ($addresses as $address)
                            <label class="checkout-option {{ $addressId == $address->id ? 'checkout-option-active' : '' }}">
                                <input type="radio" wire:model="addressId" value="{{ $address->id }}" class="shrink-0">
                                <div>
                                    <p class="font-bold text-sm text-navy">{{ $address->receiver_name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $address->city }}، {{ $address->address }}</p>
                                    @if ($address->postal_code)
                                        <p class="text-xs text-gray-400 mt-0.5">کد پستی: {{ $address->postal_code }}</p>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <p class="text-sm text-gray-600">آدرسی ثبت نشده.
                                <a href="{{ route('account.addresses') }}" class="text-brand-green font-bold">افزودن آدرس</a>
                            </p>
                        @endforelse
                        @error('addressId') <span class="text-red-600 text-xs mt-2 block">{{ $message }}</span> @enderror
                    </section>

                    <section class="shop-card p-5">
                        <h2 class="shop-section-title">روش ارسال</h2>
                        @foreach ($shippingMethods as $method)
                            <label class="checkout-option {{ $shippingMethodId == $method->id ? 'checkout-option-active' : '' }}">
                                <input type="radio" wire:model.live="shippingMethodId" value="{{ $method->id }}" class="shrink-0">
                                <div class="flex-1 flex justify-between items-center gap-2">
                                    <span class="font-medium text-sm">{{ $method->name }}</span>
                                    <span class="text-sm font-bold text-brand-green">{{ number_format($method->price) }} تومان</span>
                                </div>
                            </label>
                        @endforeach
                    </section>

                    <section class="shop-card p-5 space-y-4">
                        <div>
                            <label class="shop-label">کد تخفیف</label>
                            <input type="text" wire:model.live="couponCode" placeholder="کد تخفیف را وارد کنید" class="shop-input">
                        </div>
                        <div>
                            <label class="shop-label">یادداشت (اختیاری)</label>
                            <textarea wire:model="note" class="shop-input" rows="2" placeholder="توضیحات سفارش..."></textarea>
                        </div>
                    </section>

                    <section class="shop-card p-5">
                        <h2 class="shop-section-title">روش پرداخت</h2>
                        <p class="text-xs text-gray-500 mb-4">پرداخت اعتباری و نقدی جدا هستند. می‌توانید الان یکی را بزنید و مانده را بعداً از صفحه سفارش پرداخت کنید.</p>

                        @if (count($creditGateways))
                            <div class="checkout-pay-group">
                                <h3 class="checkout-pay-group__title">پرداخت اعتباری</h3>
                                @foreach ($creditGateways as $gateway)
                                    <label wire:key="pay-{{ $gateway['name'] }}" class="checkout-option {{ $paymentMethod === $gateway['name'] ? 'checkout-option-active' : '' }}">
                                        <input type="radio" name="paymentMethod" wire:model.live="paymentMethod" value="{{ $gateway['name'] }}" class="checkout-option__radio shrink-0">
                                        <div>
                                            <p class="font-bold text-sm">{{ $gateway['label'] }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $gateway['description'] }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        @if (count($cashGateways))
                            <div class="checkout-pay-group">
                                <h3 class="checkout-pay-group__title">درگاه‌های نقدی</h3>
                                @foreach ($cashGateways as $gateway)
                                    <label wire:key="pay-{{ $gateway['name'] }}" class="checkout-option {{ $paymentMethod === $gateway['name'] ? 'checkout-option-active' : '' }}">
                                        <input type="radio" name="paymentMethod" wire:model.live="paymentMethod" value="{{ $gateway['name'] }}" class="checkout-option__radio shrink-0">
                                        <div>
                                            <p class="font-bold text-sm">{{ $gateway['label'] }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $gateway['description'] }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="checkout-pay-group">
                            <h3 class="checkout-pay-group__title">سایر روش‌ها</h3>
                            <label class="checkout-option {{ $paymentMethod === 'cod' ? 'checkout-option-active' : '' }}">
                                <input type="radio" name="paymentMethod" wire:model.live="paymentMethod" value="cod" class="checkout-option__radio shrink-0">
                                <div>
                                    <p class="font-bold text-sm">پرداخت در محل</p>
                                    <p class="text-xs text-gray-400 mt-0.5">هنگام تحویل</p>
                                </div>
                            </label>
                        </div>
                        @error('paymentMethod') <span class="text-red-600 text-xs mt-2 block">{{ $message }}</span> @enderror
                    </section>
                </div>

                <aside class="lg:w-80 shrink-0">
                    <div class="shop-card p-5 lg:sticky lg:top-24 space-y-4">
                        <h2 class="font-bold text-navy border-b border-gray-100 pb-3">خلاصه پرداخت</h2>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>جمع کالا</span>
                                <span>{{ number_format($preview['subtotal']) }} تومان</span>
                            </div>
                            @if ($preview['discount'] > 0)
                                <div class="flex justify-between text-emerald-600">
                                    <span>تخفیف</span>
                                    <span>− {{ number_format($preview['discount']) }} تومان</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span>ارسال</span>
                                <span>{{ $preview['shipping'] === 0 ? 'رایگان' : number_format($preview['shipping']).' تومان' }}</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                            <span class="font-bold text-navy">مبلغ نهایی</span>
                            <span class="text-lg font-black text-brand-green">{{ number_format($preview['total']) }} تومان</span>
                        </div>
                        <button type="submit" wire:loading.attr="disabled" class="shop-btn-gold w-full hidden lg:block">
                            <span wire:loading.remove wire:target="placeOrder">ثبت و پرداخت سفارش</span>
                            <span wire:loading wire:target="placeOrder">در حال پردازش...</span>
                        </button>
                        <a href="{{ route('cart') }}" class="block text-center text-xs text-gray-500 hover:text-brand-green hidden lg:block">بازگشت به سبد</a>
                    </div>
                </aside>

                <div class="shop-mobile-bar lg:hidden" aria-label="ثبت سفارش">
                    <div class="shop-mobile-bar__info">
                        <span class="shop-mobile-bar__label">مبلغ نهایی</span>
                        <span class="shop-mobile-bar__price">{{ number_format($preview['total']) }} تومان</span>
                    </div>
                    <button type="submit" form="checkoutForm" wire:loading.attr="disabled" class="shop-mobile-bar__btn">
                        <span wire:loading.remove wire:target="placeOrder">ثبت سفارش</span>
                        <span wire:loading wire:target="placeOrder">...</span>
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
