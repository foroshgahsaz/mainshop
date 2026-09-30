<x-filament-panels::page>
    @php
        /** @var \App\Models\Order $order */
        $order = $this->record;
    @endphp

    <div class="admin-order" data-admin-order>
        {{-- هدر سفارش — قابل جایگزینی با HTML سفارشی --}}
        <header class="admin-order__header">
            <div class="admin-order__header-main">
                <h1 class="admin-order__title">سفارش #{{ $order->tracking_code }}</h1>
                <p class="admin-order__subtitle">
                    ثبت‌شده {{ $order->created_at?->format('Y/m/d H:i') }}
                    @if($order->user)
                        — مشتری: <strong>{{ $order->user->name }}</strong>
                    @endif
                </p>
            </div>
            <div class="admin-order__header-badges">
                <span class="admin-order__badge admin-order__badge--status admin-order__badge--{{ $order->status }}">
                    {{ \App\Support\ShopLabels::orderStatus($order->status) }}
                </span>
                <span class="admin-order__badge admin-order__badge--payment">
                    {{ \App\Support\ShopLabels::paymentMethod($order->payment_method) }}
                </span>
                @if($order->isPaid())
                    <span class="admin-order__badge admin-order__badge--paid">پرداخت شده</span>
                @elseif($order->hasSuccessfulPayment())
                    <span class="admin-order__badge">پرداخت ناقص</span>
                @endif
            </div>
        </header>

        <div class="admin-order__layout">
            {{-- ستون اصلی --}}
            <div class="admin-order__main">
                @include('filament.orders.partials.items-table', ['order' => $order])

                @if($this->canAdminEditProforma)
                    @include('filament.orders.partials.proforma-items-edit', ['order' => $order])
                @elseif($order->isProforma() && $order->isRepresentativeOrder() && $order->hasActiveStockReservation())
                    <p class="admin-order__hint admin-order__hint--warning">
                        تا پایان مهلت رزرو موجودی
                        @if($order->stock_reserved_until)
                            ({{ $order->stock_reserved_until->format('Y/m/d H:i') }})
                        @endif
                        ویرایش پیش‌فاکتور غیرفعال است. می‌توانید «تمدید رزرو موجودی» را از بالای صفحه بزنید.
                    </p>
                @endif

                @include('filament.orders.partials.totals', ['order' => $order])

                @if($order->payments->isNotEmpty())
                    @include('filament.orders.partials.payments-table', ['order' => $order])
                @endif

                @include('filament.orders.partials.notes-timeline', ['order' => $order])
            </div>

            {{-- سایدبار --}}
            <aside class="admin-order__sidebar">
                @include('filament.orders.partials.customer-box', ['order' => $order])
                @include('filament.orders.partials.address-box', ['order' => $order])
                @include('filament.orders.partials.order-actions', [
                    'order' => $order,
                    'editStatus' => $editStatus,
                    'editTracking' => $editTracking,
                ])
                @include('filament.orders.partials.order-meta', ['order' => $order])
            </aside>
        </div>
    </div>
</x-filament-panels::page>
