@props([
    'order',
    'split',
    'catalog',
])

@php
    /** @var \App\Models\Order $order */
    /** @var \App\Services\Representative\RepresentativeProformaSplitPayment $split */
    /** @var \App\Services\Payment\PaymentGatewayCatalog $catalog */
    $payments = $order->payments->sortByDesc('id');
    $usesSplit = $split->usesSplitPlan($order);
@endphp

<section class="rep-payments-ledger" aria-label="سوابق پرداخت پیش‌فاکتور">
    <header class="rep-payments-ledger__header">
        <h3 class="rep-payments-ledger__title">سوابق پرداخت</h3>
        @if ($usesSplit)
            <div class="rep-split-summary">
                <div class="rep-split-summary__item rep-split-summary__item--credit">
                    <span class="rep-split-summary__label">اعتبار</span>
                    <span class="rep-split-summary__planned">{{ \App\Support\ShopFormatter::money($split->plannedCreditAmount($order)) }}</span>
                    <span class="rep-split-summary__paid">پرداخت‌شده: {{ \App\Support\ShopFormatter::money($split->paidCreditAmount($order)) }}</span>
                    @if ($split->remainingCreditAmount($order) > 0)
                        <span class="rep-split-summary__remain">مانده: {{ \App\Support\ShopFormatter::money($split->remainingCreditAmount($order)) }}</span>
                    @endif
                </div>
                <div class="rep-split-summary__item rep-split-summary__item--cash">
                    <span class="rep-split-summary__label">نقدی</span>
                    <span class="rep-split-summary__planned">{{ \App\Support\ShopFormatter::money($split->plannedCashAmount($order)) }}</span>
                    <span class="rep-split-summary__paid">پرداخت‌شده: {{ \App\Support\ShopFormatter::money($split->paidCashAmount($order)) }}</span>
                    @if ($split->remainingCashAmount($order) > 0)
                        <span class="rep-split-summary__remain">مانده: {{ \App\Support\ShopFormatter::money($split->remainingCashAmount($order)) }}</span>
                    @endif
                </div>
            </div>
        @endif
    </header>

    @if ($payments->isEmpty())
        <p class="rep-wizard-hint">هنوز تلاشی برای پرداخت ثبت نشده است.</p>
    @else
        <ul class="rep-payments-ledger__list" role="list">
            @foreach ($payments as $payment)
                @php
                    $gw = $catalog->definition((string) $payment->gateway);
                    $status = (string) $payment->status;
                    $statusClass = match ($status) {
                        \App\Models\Payment::STATUS_SUCCESS => 'is-success',
                        \App\Models\Payment::STATUS_FAILED => 'is-failed',
                        \App\Models\Payment::STATUS_CANCELED => 'is-canceled',
                        default => 'is-pending',
                    };
                @endphp
                <li class="rep-payment-row {{ $statusClass }}" wire:key="rep-pay-{{ $payment->id }}">
                    <div class="rep-payment-row__status">
                        <span class="rep-payment-row__badge">{{ \App\Support\ShopLabels::paymentStatus($status) }}</span>
                        <time datetime="{{ $payment->created_at?->toIso8601String() }}">{{ $payment->created_at?->shopJalali() }}</time>
                    </div>
                    <div class="rep-payment-row__main">
                        <span class="rep-payment-row__gateway">
                            <x-checkout-option-icon :src="$gw['icon'] ?? null" :alt="$gw['label']" />
                            {{ $gw['label'] ?? $payment->gateway }}
                        </span>
                        <span class="rep-payment-row__amount">{{ \App\Support\ShopFormatter::money((int) $payment->amount) }}</span>
                    </div>
                    <dl class="rep-payment-row__meta">
                        <div><dt>کد پیگیری</dt><dd>{{ $payment->tracking_code }}</dd></div>
                        @if ($payment->transaction_id)
                            <div><dt>مرجع درگاه</dt><dd dir="ltr">{{ $payment->transaction_id }}</dd></div>
                        @endif
                        @if ($payment->card_number)
                            <div><dt>کارت</dt><dd dir="ltr">{{ $payment->card_number }}</dd></div>
                        @endif
                        @if ($payment->paid_at)
                            <div><dt>زمان تأیید</dt><dd>{{ $payment->paid_at->shopJalali() }}</dd></div>
                        @endif
                        @if ($payment->paidByRepresentative)
                            <div><dt>پرداخت توسط</dt><dd>نماینده {{ $payment->paidByRepresentative->name }}</dd></div>
                        @endif
                    </dl>
                </li>
            @endforeach
        </ul>
    @endif
</section>
