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
