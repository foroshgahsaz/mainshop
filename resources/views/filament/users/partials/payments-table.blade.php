@php
    use App\Support\ShopLabels;
@endphp

<div class="admin-order__table-wrap">
    <table class="admin-order__table">
        <thead>
            <tr>
                <th>کد پیگیری</th>
                <th>سفارش</th>
                <th>مبلغ</th>
                <th>درگاه</th>
                <th>وضعیت</th>
                <th>تاریخ پرداخت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td dir="ltr">{{ $payment->tracking_code ?? '—' }}</td>
                    <td dir="ltr">
                        @if ($payment->order)
                            <a href="{{ \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $payment->order]) }}"
                               class="admin-order__link">{{ $payment->order->tracking_code }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ ShopLabels::formatMoney($payment->amount) }}</td>
                    <td>{{ ShopLabels::gateway($payment->gateway) }}</td>
                    <td>
                        <span class="admin-order__badge admin-order__badge--payment-status admin-order__badge--pay-{{ $payment->status }}">
                            {{ ShopLabels::paymentStatus($payment->status) }}
                        </span>
                    </td>
                    <td>
                        @if ($payment->paid_at)
                            {{ $payment->paid_at->format('Y/m/d H:i') }}
                        @else
                            {{ $payment->created_at?->format('Y/m/d H:i') ?? '—' }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ \App\Filament\Resources\PaymentResource::getUrl('view', ['record' => $payment]) }}"
                           class="admin-order__link">جزئیات</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="admin-order__empty">پرداختی برای این کاربر ثبت نشده است.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
