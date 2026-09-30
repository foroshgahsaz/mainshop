<x-filament-panels::page>
    @php
        /** @var \App\Models\Order $order */
        $order = $this->record->loadMissing(['items', 'user', 'freightCarrier.province', 'freightCarrier.city']);
    @endphp

    <link rel="stylesheet" href="{{ asset('css/rep-order-wizard.css') }}">

    <section class="rep-wizard-panel">
        <p class="rep-wizard-hint">
            مشتری: <strong>{{ $order->user?->name }}</strong> ({{ $order->user?->phone }})
        </p>
        <p class="rep-wizard-hint">
            وضعیت: <strong>پیش‌فاکتور ثبت‌شده</strong> — فقط مشاهده
        </p>
        <ul class="rep-product-list">
            @forelse ($order->items as $item)
                <li class="rep-product-row">
                    <div>
                        <div class="rep-choice-title">{{ $item->product_name }}</div>
                        <div class="rep-choice-meta">
                            {{ $item->quantity }} × {{ \App\Support\ShopFormatter::money($item->price) }}
                            = {{ \App\Support\ShopFormatter::money($item->total_price) }}
                        </div>
                    </div>
                </li>
            @empty
                <li class="rep-wizard-empty">اقلامی ثبت نشده.</li>
            @endforelse
        </ul>

        <div class="rep-wizard-actions" style="margin-top: 0.75rem;">
            <a href="{{ route('representative.proforma.pdf', $order) }}"
               class="rep-btn-secondary"
               target="_blank"
               rel="noopener">
                دانلود PDF پیش‌فاکتور
            </a>
        </div>

        @if ($order->isProforma() && $order->remainingAmount() > 0)
            @php
                $customerPayUrl = route('account.orders.show', $order);
            @endphp
            <div class="rep-payment-info">
                <h3 class="rep-payment-info__title">پرداخت آنلاین (سمت مشتری)</h3>
                <p class="rep-wizard-hint rep-payment-info__lead">
                    نماینده از این پنل پرداخت نمی‌کند. مشتری باید با <strong>همان حساب کاربری</strong> که برایش ثبت کرده‌اید
                    در فروشگاه وارد شود و پیش‌فاکتور را بپردازد.
                </p>
                <label class="rep-payment-info__label" for="repCustomerPayUrl">لینک پرداخت برای ارسال به مشتری</label>
                <div class="rep-payment-info__url-row">
                    <input type="text"
                           id="repCustomerPayUrl"
                           class="rep-payment-info__url"
                           value="{{ $customerPayUrl }}"
                           readonly
                           dir="ltr">
                    <button type="button"
                            class="rep-btn-secondary rep-payment-info__copy"
                            onclick="navigator.clipboard.writeText(@js($customerPayUrl)); this.textContent='کپی شد'; setTimeout(() => this.textContent='کپی لینک', 2000);">
                        کپی لینک
                    </button>
                </div>
                <p class="rep-wizard-hint rep-payment-info__steps">
                    تست: ۱) <code>php artisan migrate</code> روی سرور ۲) پیش‌فاکتور <strong>جدید</strong> ثبت کنید (ثبت مجدد رزرو موجودی می‌سازد)
                    ۳) با موبایل/مرورگر دیگر به‌عنوان <strong>مشتری</strong> لاگین کنید ۴) منوی کاربری → سفارش‌ها → جزئیات → «پرداخت پیش‌فاکتور».
                </p>
                @if (! $order->stock_reserved)
                    <p class="rep-wizard-error">
                        این پیش‌فاکتور قبل از فعال‌سازی رزرو موجودی ثبت شده؛ دکمه پرداخت درگاه برای مشتری کار نمی‌کند.
                        یک پیش‌فاکتور جدید بسازید یا از ادمین وضعیت رزرو را بررسی کنید.
                    </p>
                @elseif (! $order->hasActiveStockReservation())
                    <p class="rep-wizard-error">
                        مهلت رزرو موجودی تمام شده؛ مشتری نمی‌تواند پرداخت کند. ادمین می‌تواند از جزئیات سفارش، «تمدید رزرو موجودی» را بزند.
                    </p>
                @else
                    <p class="rep-wizard-hint">
                        رزرو موجودی فعال است — مشتری تا
                        <strong>{{ $order->stock_reserved_until?->format('Y/m/d H:i') }}</strong>
                        می‌تواند پرداخت کند.
                    </p>
                @endif
            </div>
        @elseif ($order->isProforma() && $order->isPaid())
            <p class="rep-wizard-hint rep-payment-info__paid">این پیش‌فاکتور تسویه شده است.</p>
        @endif

        <div class="rep-order-totals">
            <div>جمع اقلام: {{ \App\Support\ShopFormatter::money((int) $order->total_amount) }}</div>
            @if ($order->freightCarrier)
                <div>باربری: {{ $order->freightCarrier->displayLabel() }}</div>
            @endif
            <div>درگاه پرداخت: {{ \App\Support\ShopLabels::paymentMethod($order->payment_method) }}</div>
            <div class="rep-order-final">مبلغ نهایی: {{ \App\Support\ShopFormatter::money((int) $order->final_amount) }}</div>
        </div>

    </section>
</x-filament-panels::page>
