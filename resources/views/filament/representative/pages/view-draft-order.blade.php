<x-filament-panels::page>
    @php
        /** @var \App\Models\Order $order */
        $order = $this->record->loadMissing(['items', 'user', 'freightCarrier.province', 'freightCarrier.city', 'payments']);
        $gatewayDef = app(\App\Services\Payment\PaymentGatewayCatalog::class)->definition((string) $order->payment_method);
        $gatewayLabel = $gatewayDef['label'] ?? \App\Support\ShopLabels::paymentMethod($order->payment_method);
        $gatewayIcon = $gatewayDef['icon'] ?? null;
    @endphp

    <link rel="stylesheet" href="{{ asset('css/rep-order-wizard.css') }}?v={{ filemtime(public_path('css/rep-order-wizard.css')) }}">
    <link rel="stylesheet" href="{{ asset('shop/css/sales-invoice.css') }}?v={{ filemtime(public_path('shop/css/sales-invoice.css')) }}">

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
        <div class="rep-proforma-invoice">
            <div class="si-document--cart-wrap">
                @include('components.sales-invoice.document', ['document' => $invoicePreview, 'context' => 'web'])
            </div>
        </div>

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

                <div class="rep-proforma-pay-row">
                    <div class="rep-proforma-gateway">
                        <span class="rep-proforma-gateway__label">درگاه پرداخت</span>
                        <span class="rep-proforma-gateway__value">
                            <x-checkout-option-icon :src="$gatewayIcon" :alt="$gatewayLabel" />
                            <strong>{{ $gatewayLabel }}</strong>
                        </span>
                    </div>
                    <div class="rep-proforma-amount">
                        <span class="rep-proforma-gateway__label">مبلغ قابل پرداخت</span>
                        <strong class="rep-proforma-amount__value">{{ \App\Support\ShopFormatter::money($order->remainingAmount()) }}</strong>
                    </div>
                </div>

                @if ($order->canRepresentativePayProforma())
                    <button type="button"
                            class="rep-btn-primary rep-payment-info__pay"
                            wire:click="payProformaFromPage"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="payProformaFromPage">پرداخت و رفتن به درگاه</span>
                        <span wire:loading wire:target="payProformaFromPage">در حال اتصال…</span>
                    </button>
                    @if ($order->stock_reserved_until)
                        <p class="rep-wizard-hint rep-proforma-reservation">
                            مهلت رزرو موجودی تا <strong>{{ $order->stock_reserved_until->format('Y/m/d H:i') }}</strong>
                        </p>
                    @endif
                @elseif (! $order->stock_reserved)
                    <p class="rep-wizard-error">
                        رزرو موجودی فعال نیست. با ادمین هماهنگ کنید تا پیش‌فاکتور ویرایش یا رزرو مجدد شود.
                    </p>
                @elseif (! $order->hasActiveStockReservation())
                    <p class="rep-wizard-error">
                        مهلت رزرو تمام شده. ادمین می‌تواند پیش‌فاکتور را ویرایش کند؛ برای پرداخت، از ادمین «تمدید رزرو موجودی» بگیرید.
                    </p>
                @endif
            </div>
        @elseif ($order->isProforma() && $order->isPaid())
            <p class="rep-wizard-hint rep-payment-info__paid">این پیش‌فاکتور تسویه شده است.</p>
        @endif

        <dl class="rep-proforma-summary">
            <div class="rep-proforma-summary__row">
                <dt>جمع اقلام</dt>
                <dd>{{ \App\Support\ShopFormatter::money((int) $order->total_amount) }}</dd>
            </div>
            @if ($order->freightCarrier)
                <div class="rep-proforma-summary__row">
                    <dt>باربری</dt>
                    <dd>{{ $order->freightCarrier->displayLabel() }}</dd>
                </div>
            @endif
            <div class="rep-proforma-summary__row rep-proforma-summary__row--gateway">
                <dt>درگاه پرداخت</dt>
                <dd class="rep-proforma-summary__gateway">
                    <x-checkout-option-icon :src="$gatewayIcon" :alt="$gatewayLabel" />
                    <span>{{ $gatewayLabel }}</span>
                </dd>
            </div>
            @if ($order->paidAmount() > 0)
                <div class="rep-proforma-summary__row">
                    <dt>پرداخت‌شده</dt>
                    <dd>{{ \App\Support\ShopFormatter::money($order->paidAmount()) }}</dd>
                </div>
            @endif
            <div class="rep-proforma-summary__row rep-proforma-summary__row--total">
                <dt>مبلغ نهایی</dt>
                <dd>{{ \App\Support\ShopFormatter::money((int) $order->final_amount) }}</dd>
            </div>
        </dl>

    </section>
</x-filament-panels::page>
