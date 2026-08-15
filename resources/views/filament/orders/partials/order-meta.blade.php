<section class="admin-order__box admin-order__box--meta">
    <h3 class="admin-order__box-title">اطلاعات سفارش</h3>
    <dl class="admin-order__meta-list">
        <div><dt>شناسه</dt><dd>#{{ $order->id }}</dd></div>
        <div><dt>کد پیگیری</dt><dd dir="ltr">{{ $order->tracking_code }}</dd></div>
        <div><dt>تاریخ ثبت</dt><dd>{{ $order->created_at?->format('Y/m/d H:i') }}</dd></div>
        <div><dt>آخرین به‌روزرسانی</dt><dd>{{ $order->updated_at?->format('Y/m/d H:i') }}</dd></div>
        @if($order->shipped_at)
            <div><dt>تاریخ ارسال</dt><dd>{{ $order->shipped_at->format('Y/m/d H:i') }}</dd></div>
        @endif
        @if($order->delivered_at)
            <div><dt>تاریخ تحویل</dt><dd>{{ $order->delivered_at->format('Y/m/d H:i') }}</dd></div>
        @endif
        @if($order->shipping_tracking_code)
            <div><dt>رهگیری پست</dt><dd dir="ltr">{{ $order->shipping_tracking_code }}</dd></div>
        @endif
        @if($order->shippingMethod)
            <div><dt>روش ارسال</dt><dd>{{ $order->shippingMethod->name }}</dd></div>
        @endif
    </dl>
</section>
