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
        @if ($order->stock_reserved_until)
            <p class="rep-wizard-hint {{ $order->hasActiveStockReservation() ? '' : 'rep-wizard-error' }}">
                @if ($order->hasActiveStockReservation())
                    مهلت پرداخت مشتری تا: <strong>{{ $order->stock_reserved_until->format('Y/m/d H:i') }}</strong>
                @else
                    مهلت رزرو موجودی به پایان رسیده است.
                @endif
            </p>
        @endif

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
            @if ($order->hasActiveStockReservation() && $order->remainingAmount() > 0)
                <a href="{{ route('account.orders.show', $order) }}"
                   class="rep-btn-secondary"
                   target="_blank"
                   rel="noopener">
                    صفحه پرداخت مشتری
                </a>
            @endif
        </div>

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
