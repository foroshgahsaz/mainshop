<section class="admin-order__box admin-order__box--customer">
    <h3 class="admin-order__box-title">مشتری</h3>
    @if($order->user)
        <dl class="admin-order__meta-list">
            <div><dt>نام</dt><dd>{{ $order->user->name }}</dd></div>
            <div><dt>موبایل</dt><dd dir="ltr">{{ $order->user->phone }}</dd></div>
            @if($order->user->email)
                <div><dt>ایمیل</dt><dd dir="ltr">{{ $order->user->email }}</dd></div>
            @endif
            <div><dt>تعداد سفارش</dt><dd>{{ $order->user->orders()->count() }}</dd></div>
        </dl>
    @else
        <p class="admin-order__empty">کاربر حذف شده</p>
    @endif
</section>
