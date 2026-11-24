@php
    use App\Models\Payment;
    use App\Services\Payment\PaymentGatewayCatalog;
    use App\Services\Representative\RepresentativeProformaSplitPayment;
    use App\Support\AdminAccess;

    $split = app(RepresentativeProformaSplitPayment::class);
    $catalog = app(PaymentGatewayCatalog::class);
    $creditPaid = $split->paidCreditAmount($order);
    $cashPaid = $split->paidCashAmount($order);
    $plannedCredit = $split->plannedCreditAmount($order);
    $plannedCash = $split->plannedCashAmount($order);
@endphp

@if (AdminAccess::canViewFinancialsInAdmin())
    <section class="admin-order__section admin-order__section--financial">
        <h2 class="admin-order__section-title">خلاصه مالی</h2>
        <dl class="admin-order__dl">
            <dt>طرح اعتباری</dt>
            <dd>{{ $order->creditPlan?->name ?? '—' }}</dd>
            <dt>مبلغ نهایی</dt>
            <dd>{{ number_format((int) $order->final_amount) }} تومان</dd>
            @if ($split->usesSplitPlan($order))
                <dt>سهم اعتباری (طرح)</dt>
                <dd>{{ number_format($plannedCredit) }} تومان — پرداخت‌شده: {{ number_format($creditPaid) }} تومان</dd>
                <dt>سهم نقدی (طرح)</dt>
                <dd>{{ number_format($plannedCash) }} تومان — پرداخت‌شده: {{ number_format($cashPaid) }} تومان</dd>
            @else
                <dt>پرداخت‌شده</dt>
                <dd>{{ number_format($order->paidAmount()) }} تومان @if($order->remainingAmount() > 0) (مانده: {{ number_format($order->remainingAmount()) }}) @endif</dd>
            @endif
        </dl>
        @php
            $successful = $order->payments->where('status', Payment::STATUS_SUCCESS);
        @endphp
        @if ($successful->isNotEmpty())
            <ul class="admin-order__financial-list text-sm text-gray-700 space-y-1 mt-3">
                @foreach ($successful as $payment)
                    @php
                        $gwType = $catalog->definition((string) $payment->gateway)['type'] ?? PaymentGatewayCatalog::TYPE_CASH;
                        $typeLabel = $gwType === PaymentGatewayCatalog::TYPE_CREDIT ? 'اعتباری' : 'نقدی';
                    @endphp
                    <li>
                        <span dir="ltr">{{ $payment->tracking_code }}</span>
                        — {{ \App\Support\ShopLabels::gateway($payment->gateway) }} ({{ $typeLabel }})
                        — {{ number_format((int) $payment->amount) }} تومان
                        @if ($payment->paid_at)
                            <span class="text-gray-500">— {{ $payment->paid_at->shopJalali() }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endif