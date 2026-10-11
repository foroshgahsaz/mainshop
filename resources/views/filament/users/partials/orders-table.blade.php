@php
    use App\Support\ShopLabels;
@endphp

<div class="admin-order__table-wrap">
    <table class="admin-order__table">
        <thead>
            <tr>
                <th>کد سفارش</th>
                <th>مبلغ</th>
                <th>روش پرداخت</th>
                <th>وضعیت</th>
                <th>تعداد اقلام</th>
                <th>تاریخ ثبت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td dir="ltr">{{ $order->tracking_code }}</td>
                    <td>{{ ShopLabels::formatMoney($order->final_amount) }}</td>
                    <td>{{ ShopLabels::paymentMethod($order->payment_method) }}</td>
                    <td>
                        <span class="admin-order__badge admin-order__badge--status admin-order__badge--{{ $order->status }}">
                            {{ ShopLabels::orderStatus($order->status) }}
                        </span>
                    </td>
                    <td>{{ $order->items_count ?? $order->items->count() }}</td>
                    <td>{{ $order->created_at?->shopJalali() }}</td>
                    <td>
                        <a href="{{ \App\Filament\Resources\OrderResource::getUrl('view', ['record' => $order]) }}"
                           class="admin-order__link">جزئیات</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="admin-order__empty">سفارشی برای این کاربر ثبت نشده است.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
