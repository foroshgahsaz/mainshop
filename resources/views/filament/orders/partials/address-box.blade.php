<section class="admin-order__box admin-order__box--address">
    <h3 class="admin-order__box-title">آدرس تحویل</h3>
    @if($order->address)
        <dl class="admin-order__meta-list">
            <div><dt>گیرنده</dt><dd>{{ $order->address->receiver_name }}</dd></div>
            <div><dt>موبایل</dt><dd dir="ltr">{{ $order->address->receiver_phone }}</dd></div>
            <div><dt>استان / شهر</dt><dd>{{ $order->address->province }} — {{ $order->address->city }}</dd></div>
            <div><dt>آدرس</dt><dd>{{ $order->address->address }}</dd></div>
            <div><dt>کد پستی</dt><dd dir="ltr">{{ $order->address->postal_code }}</dd></div>
        </dl>
    @else
        <p class="admin-order__empty">آدرس ثبت نشده</p>
    @endif
</section>

@if($order->note)
<section class="admin-order__box admin-order__box--customer-note">
    <h3 class="admin-order__box-title">یادداشت مشتری هنگام خرید</h3>
    <p class="admin-order__customer-note">{{ $order->note }}</p>
</section>
@endif
