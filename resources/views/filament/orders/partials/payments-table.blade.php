<section class="admin-order__section admin-order__section--payments">
    <h2 class="admin-order__section-title">پرداخت‌ها</h2>
    <div class="admin-order__table-wrap">
        <table class="admin-order__table">
            <thead>
                <tr>
                    <th>کد پیگیری</th>
                    <th>درگاه</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>پرداخت‌کننده</th>
                    <th>تاریخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->payments as $payment)
                    <tr>
                        <td dir="ltr">{{ $payment->tracking_code }}</td>
                        <td>{{ \App\Support\ShopLabels::gateway($payment->gateway) }}</td>
                        <td>{{ number_format($payment->amount) }} تومان</td>
                        <td>
                            <span class="admin-order__badge admin-order__badge--payment-status admin-order__badge--pay-{{ $payment->status }}">
                                {{ \App\Support\ShopLabels::paymentStatus($payment->status) }}
                            </span>
                        </td>
                        <td>
                            @if ($payment->wasPaidByRepresentative())
                                نماینده: {{ $payment->paidByRepresentative?->name ?? '—' }}
                                <span class="text-gray-500 text-xs block">مشتری: {{ $payment->user?->name }}</span>
                            @else
                                {{ $payment->user?->name ?? '—' }}
                            @endif
                        </td>
                        <td>
                            @if($payment->paid_at)
                                {{ $payment->paid_at->shopJalali() }}
                            @else
                                {{ $payment->created_at?->shopJalali() }}
                            @endif
                        </td>
                        <td>
                            <a href="{{ \App\Filament\Resources\PaymentResource::getUrl('view', ['record' => $payment]) }}"
                               class="admin-order__link">جزئیات</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
