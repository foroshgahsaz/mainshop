<x-filament-panels::page>
    @php
        /** @var \App\Models\Order $order */
        $order = $this->record->loadMissing(['items', 'user', 'freightCarrier.province', 'freightCarrier.city', 'payments']);
    @endphp

    <link rel="stylesheet" href="{{ asset('css/rep-order-wizard.css') }}">
    <link rel="stylesheet" href="{{ asset('shop/css/sales-invoice.css') }}">

    @if (session('payment_status'))
        <div class="rep-payment-flash {{ session('payment_status') === 'success' ? 'rep-payment-flash--ok' : 'rep-payment-flash--fail' }}">
            @if (session('payment_status') === 'success')
                پرداخت با موفقیت ثبت شد.
            @else
                پرداخت ناموفق یا لغو شد.
            @endif
        </div>
    @endif

    <section class="rep-wizard-panel">
        <p class="rep-wizard-hint">
            مشتری: <strong>{{ $order->user?->name }}</strong> ({{ $order->user?->phone }})
        </p>
        <p class="rep-wizard-hint">
            وضعیت: <strong>پیش‌فاکتور ثبت‌شده</strong> — فقط مشاهده
        </p>

        @php
            $invoicePreview = \App\Support\SalesInvoice\SalesInvoiceBuilder::fromOrder(
                $order,
                'پیش‌فاکتور',
                app(\App\Services\Settings\SettingsService::class)->site(),
            );
        @endphp
        <div class="si-document--cart-wrap" style="margin-bottom: 1rem;">
            @include('components.sales-invoice.document', ['document' => $invoicePreview, 'context' => 'web'])
        </div>
        @push('styles')
            <link rel="stylesheet" href="{{ asset('shop/css/sales-invoice.css') }}">
        @endpush

        <div class="rep-wizard-actions" style="margin-top: 0.75rem;">
            <a href="{{ route('representative.proforma.pdf', $order) }}"
               class="rep-btn-secondary"
               target="_blank"
               rel="noopener">
                دانلود PDF پیش‌فاکتور
            </a>
        </div>

        @if ($order->isProforma() && $order->remainingAmount() > 0)
            <div class="rep-payment-info">
                <h3 class="rep-payment-info__title">پرداخت پیش‌فاکتور</h3>
                <p class="rep-wizard-hint rep-payment-info__lead">
                    پرداخت از <strong>پنل نماینده</strong> انجام می‌شود؛ در درگاه مشخص می‌شود این پرداخت
                    برای کدام <strong>مشتری</strong> و توسط کدام <strong>نماینده</strong> است.
                </p>
                <p class="rep-wizard-hint">
                    درگاه: <strong>{{ \App\Support\ShopLabels::paymentMethod($order->payment_method) }}</strong>
                    — مبلغ: <strong>{{ \App\Support\ShopFormatter::money($order->remainingAmount()) }}</strong>
                </p>

                @if ($order->canRepresentativePayProforma())
                    <button type="button"
                            class="rep-btn-primary rep-payment-info__pay"
                            wire:click="payProformaFromPage"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="payProformaFromPage">پرداخت و رفتن به درگاه</span>
                        <span wire:loading wire:target="payProformaFromPage">در حال اتصال…</span>
                    </button>
                    @if ($order->stock_reserved_until)
                        <p class="rep-wizard-hint">
                            مهلت رزرو موجودی تا <strong>{{ $order->stock_reserved_until->format('Y/m/d H:i') }}</strong>
                        </p>
                    @endif
                @elseif (! $order->stock_reserved)
                    <p class="rep-wizard-error">
                        رزرو موجودی فعال نیست (پیش‌فاکتور قبل از به‌روزرسانی سیستم). یک پیش‌فاکتور جدید ثبت کنید یا migrate را اجرا کنید.
                    </p>
                @elseif (! $order->hasActiveStockReservation())
                    <p class="rep-wizard-error">مهلت رزرو تمام شده — از ادمین «تمدید رزرو موجودی» بگیرید.</p>
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
            @if ($order->paidAmount() > 0)
                <div>پرداخت‌شده: {{ \App\Support\ShopFormatter::money($order->paidAmount()) }}</div>
            @endif
        </div>

    </section>
</x-filament-panels::page>
