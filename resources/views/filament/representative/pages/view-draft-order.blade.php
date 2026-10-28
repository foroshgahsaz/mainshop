<x-filament-panels::page>
    @php
        /** @var \App\Models\Order $order */
        $order = $this->record->loadMissing(['items', 'user', 'freightCarrier.province', 'freightCarrier.city', 'payments']);
        $gatewayDef = app(\App\Services\Payment\PaymentGatewayCatalog::class)->definition((string) $order->payment_method);
        $gatewayLabel = $gatewayDef['label'] ?? \App\Support\ShopLabels::paymentMethod($order->payment_method);
        $gatewayIcon = $gatewayDef['icon'] ?? null;
        $splitPay = app(\App\Services\Representative\RepresentativeProformaSplitPayment::class);
        $catalog = app(\App\Services\Payment\PaymentGatewayCatalog::class);
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

        @if ($order->isProforma())
            <x-rep-proforma-payments-ledger :order="$order" :split="$splitPay" :catalog="$catalog" />
        @endif

        <div class="rep-wizard-actions" style="margin-top: 0.75rem;">
            <a href="{{ route('representative.proforma.pdf', $order) }}"
               class="rep-btn-secondary"
               target="_blank"
               rel="noopener">
                دانلود PDF پیش‌فاکتور
            </a>
        </div>

        @php
            $usesSplit = $splitPay->usesSplitPlan($order);
            $canPay = $order->canRepresentativePayProforma();
            $cashGwDef = $catalog->definition($splitPay->cashGateway($order));
        @endphp

        @if ($order->isProforma() && ! $order->isPaid())
            <div class="rep-payment-info rep-payment-info--split">
                <h3 class="rep-payment-info__title">پرداخت پیش‌فاکتور</h3>
                <p class="rep-wizard-hint rep-payment-info__lead">
                    پرداخت از <strong>پنل نماینده</strong> برای مشتری <strong>{{ $order->user?->name }}</strong> ثبت می‌شود.
                    @if ($usesSplit)
                        تا وقتی <strong>مجموع اعتبار و نقد</strong> با مبلغ فاکتور برابر نشود، پیش‌فاکتور بسته نمی‌شود.
                    @endif
                </p>

                @if (! $order->payments()->where('status', \App\Models\Payment::STATUS_SUCCESS)->exists())
                    @php $gwType = $catalog->definition((string) $order->payment_method)['type'] ?? 'cash'; @endphp
                    @if ($gwType === \App\Services\Payment\PaymentGatewayCatalog::TYPE_CREDIT)
                        <div class="rep-split-plan rep-split-plan--inline">
                            <label class="rep-field rep-field--grow">
                                <span class="rep-field__label">سهم اعتباری (ریال)</span>
                                <input type="text"
                                       inputmode="numeric"
                                       wire:model.live="plannedCreditEditRial"
                                       class="rep-field__input rep-field__input--rial-lg"
                                       dir="ltr"
                                       autocomplete="off">
                            </label>
                            <button type="button" class="rep-btn-secondary" wire:click="saveSplitPaymentPlan">ذخیره طرح</button>
                            <p class="rep-wizard-hint">مابقی = نقدی (پرداخت چندمرحله‌ای از درگاه {{ $cashGwDef['label'] ?? 'نقدی' }})</p>
                        </div>
                    @endif
                @endif

                @if ($usesSplit)
                    <div class="rep-split-summary rep-split-summary--panel">
                        <div class="rep-split-summary__item rep-split-summary__item--credit">
                            <span class="rep-split-summary__label">اعتبار · {{ $gatewayLabel }}</span>
                            <strong>{{ \App\Support\ShopFormatter::money($splitPay->plannedCreditAmount($order)) }}</strong>
                            <span class="rep-split-summary__paid">پرداخت‌شده {{ \App\Support\ShopFormatter::money($splitPay->paidCreditAmount($order)) }}</span>
                        </div>
                        <div class="rep-split-summary__item rep-split-summary__item--cash">
                            <span class="rep-split-summary__label">نقدی · {{ $cashGwDef['label'] ?? '—' }}</span>
                            <strong>{{ \App\Support\ShopFormatter::money($splitPay->plannedCashAmount($order)) }}</strong>
                            <span class="rep-split-summary__paid">پرداخت‌شده {{ \App\Support\ShopFormatter::money($splitPay->paidCashAmount($order)) }}</span>
                        </div>
                        <div class="rep-split-summary__item rep-split-summary__item--total">
                            <span class="rep-split-summary__label">مانده کل سفارش</span>
                            <strong>{{ \App\Support\ShopFormatter::money($order->remainingAmount()) }}</strong>
                        </div>
                    </div>

                    @if ($canPay && $splitPay->remainingCashAmount($order) > 0)
                        <div class="rep-cash-pay-box">
                            <h4 class="rep-cash-pay-box__title">پرداخت نقدی (چندمرحله‌ای)</h4>
                            <p class="rep-wizard-hint">مانده نقد: <strong>{{ \App\Support\ShopFormatter::money($splitPay->remainingCashAmount($order)) }}</strong></p>
                            <div class="rep-cash-pay-box__row">
                                <label class="rep-field rep-field--grow">
                                    <span class="rep-field__label">مبلغ این پرداخت (ریال)</span>
                                    <input type="text"
                                           inputmode="numeric"
                                           wire:model.live="cashPayAmount"
                                           class="rep-field__input rep-field__input--rial-lg"
                                           placeholder="مثلاً {{ number_format($splitPay->remainingCashAmount($order) * 10) }}"
                                           dir="ltr"
                                           autocomplete="off">
                                </label>
                                <button type="button"
                                        class="rep-btn-primary"
                                        wire:click="payCashPortion"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="payCashPortion">پرداخت نقدی</span>
                                    <span wire:loading wire:target="payCashPortion">…</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($canPay && $splitPay->remainingCreditAmount($order) > 0)
                        <button type="button"
                                class="rep-btn-primary rep-payment-info__pay"
                                wire:click="payCreditPortion"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="payCreditPortion">پرداخت اعتبار ({{ \App\Support\ShopFormatter::money($splitPay->remainingCreditAmount($order)) }})</span>
                            <span wire:loading wire:target="payCreditPortion">در حال اتصال…</span>
                        </button>
                    @endif
                @else
                    <div class="rep-proforma-pay-row">
                        <div class="rep-proforma-gateway">
                            <span class="rep-proforma-gateway__label">درگاه پرداخت</span>
                            <span class="rep-proforma-gateway__value">
                                <x-checkout-option-icon :src="$gatewayIcon" :alt="$gatewayLabel" />
                                <strong>{{ $gatewayLabel }}</strong>
                            </span>
                        </div>
                        <div class="rep-proforma-amount">
                            <span class="rep-proforma-gateway__label">مانده قابل پرداخت</span>
                            <strong class="rep-proforma-amount__value">{{ \App\Support\ShopFormatter::money($order->remainingAmount()) }}</strong>
                        </div>
                    </div>

                    @if ($canPay)
                        <button type="button"
                                class="rep-btn-primary rep-payment-info__pay"
                                wire:click="payProformaFromPage"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="payProformaFromPage">پرداخت و رفتن به درگاه</span>
                            <span wire:loading wire:target="payProformaFromPage">در حال اتصال…</span>
                        </button>
                    @endif
                @endif

                @if ($canPay && $order->stock_reserved_until)
                    <p class="rep-wizard-hint rep-proforma-reservation">
                        مهلت رزرو موجودی تا <strong>{{ $order->stock_reserved_until->shopJalali() }}</strong>
                    </p>
                @elseif (! $order->hasActiveStockReservation())
                    @php($reservationRequest = $order->latestReservationRequest()->first())
                    <p class="rep-wizard-error">
                        @if (! $order->stock_reserved)
                            رزرو موجودی فعال نیست.
                        @else
                            مهلت رزرو تمام شده.
                        @endif
                        برای پرداخت، درخواست رزرو مجدد را برای مدیر منطقه بفرستید.
                    </p>
                    @if ($reservationRequest?->isPending())
                        <p class="rep-wizard-hint rep-reservation-request rep-reservation-request--pending">
                            درخواست رزرو مجدد در {{ $reservationRequest->created_at->shopJalali() }} ثبت شد و در انتظار بررسی مدیر منطقه است.
                        </p>
                    @else
                        @if ($reservationRequest?->status === \App\Models\ProformaReservationRequest::STATUS_REJECTED)
                            <p class="rep-wizard-hint rep-reservation-request rep-reservation-request--rejected">
                                درخواست قبلی رد شد{{ $reservationRequest->response_note ? ': '.$reservationRequest->response_note : '.' }}
                            </p>
                        @endif
                        <button type="button"
                                class="rep-btn-primary rep-payment-info__pay"
                                wire:click="requestReReservation"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="requestReReservation">درخواست رزرو مجدد</span>
                            <span wire:loading wire:target="requestReReservation">در حال ثبت…</span>
                        </button>
                    @endif
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
