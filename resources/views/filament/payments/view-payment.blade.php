<x-filament-panels::page>
    @php
        /** @var \App\Models\Payment $payment */
        $payment = $this->record;
        $order = $payment->order;
    @endphp

    <div class="admin-order admin-payment" data-admin-payment>
        <header class="admin-order__header">
            <div class="admin-order__header-main">
                <h1 class="admin-order__title">پرداخت #{{ $payment->tracking_code }}</h1>
                <p class="admin-order__subtitle">
                    @if($payment->paid_at)
                        پرداخت‌شده {{ $payment->paid_at->format('Y/m/d H:i') }}
                    @else
                        ایجاد‌شده {{ $payment->created_at?->format('Y/m/d H:i') }}
                    @endif
                    @if($order)
                        — سفارش: <a href="{{ \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $order]) }}" class="admin-order__link">#{{ $order->tracking_code }}</a>
                    @endif
                </p>
            </div>
            <div class="admin-order__header-badges">
                <span class="admin-order__badge admin-order__badge--payment-status admin-order__badge--pay-{{ $payment->status }}">
                    {{ \App\Support\ShopLabels::paymentStatus($payment->status) }}
                </span>
                <span class="admin-order__badge">{{ $payment->gateway }}</span>
            </div>
        </header>

        <div class="admin-order__layout">
            <div class="admin-order__main">
                <section class="admin-order__section">
                    <h2 class="admin-order__section-title">جزئیات تراکنش</h2>
                    <dl class="admin-order__detail-grid">
                        <div><dt>مبلغ</dt><dd>{{ number_format($payment->amount) }} تومان</dd></div>
                        <div><dt>درگاه</dt><dd>{{ $payment->gateway }}</dd></div>
                        <div><dt>کد پیگیری</dt><dd dir="ltr">{{ $payment->tracking_code }}</dd></div>
                        @if($payment->transaction_id)
                            <div><dt>Authority / تراکنش</dt><dd dir="ltr">{{ $payment->transaction_id }}</dd></div>
                        @endif
                        @if($payment->card_number)
                            <div><dt>شماره کارت</dt><dd dir="ltr">{{ $payment->card_number }}</dd></div>
                        @endif
                        <div><dt>تاریخ ایجاد</dt><dd>{{ $payment->created_at?->format('Y/m/d H:i') }}</dd></div>
                        @if($payment->paid_at)
                            <div><dt>تاریخ پرداخت</dt><dd>{{ $payment->paid_at->format('Y/m/d H:i') }}</dd></div>
                        @endif
                    </dl>
                </section>

                @if($payment->raw_response)
                    <section class="admin-order__section">
                        <h2 class="admin-order__section-title">پاسخ درگاه</h2>
                        <pre class="admin-order__raw-json" dir="ltr">{{ json_encode($payment->raw_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </section>
                @endif

                @include('filament.payments.partials.notes-timeline', ['payment' => $payment])
            </div>

            <aside class="admin-order__sidebar">
                @if($payment->user)
                    <section class="admin-order__box">
                        <h3 class="admin-order__box-title">پرداخت‌کننده</h3>
                        <dl class="admin-order__meta-list">
                            <div><dt>نام</dt><dd>{{ $payment->user->name }}</dd></div>
                            <div><dt>موبایل</dt><dd dir="ltr">{{ $payment->user->phone }}</dd></div>
                        </dl>
                    </section>
                @endif

                @if($order)
                    <section class="admin-order__box">
                        <h3 class="admin-order__box-title">سفارش مرتبط</h3>
                        <dl class="admin-order__meta-list">
                            <div><dt>کد</dt><dd dir="ltr">{{ $order->tracking_code }}</dd></div>
                            <div><dt>وضعیت</dt><dd>{{ \App\Support\ShopLabels::orderStatus($order->status) }}</dd></div>
                            <div><dt>مبلغ سفارش</dt><dd>{{ number_format($order->final_amount) }} تومان</dd></div>
                            <div><dt>روش</dt><dd>{{ \App\Support\ShopLabels::paymentMethod($order->payment_method) }}</dd></div>
                        </dl>
                        <a href="{{ \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $order]) }}"
                           class="admin-order__btn admin-order__btn--secondary admin-order__btn--block">
                            مشاهده سفارش
                        </a>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</x-filament-panels::page>
