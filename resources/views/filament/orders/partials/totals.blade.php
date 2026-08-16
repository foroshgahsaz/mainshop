<section class="admin-order__section admin-order__section--totals">
    <h2 class="admin-order__section-title">خلاصه مبالغ</h2>
    <dl class="admin-order__totals">
        <div class="admin-order__totals-row">
            <dt>جمع اقلام</dt>
            <dd>{{ number_format($order->total_amount) }} تومان</dd>
        </div>
        @if($order->discount_amount > 0)
            <div class="admin-order__totals-row admin-order__totals-row--discount">
                <dt>تخفیف @if($order->coupon)({{ $order->coupon->code }})@endif</dt>
                <dd>−{{ number_format($order->discount_amount) }} تومان</dd>
            </div>
        @endif
        <div class="admin-order__totals-row">
            <dt>هزینه ارسال @if($order->shippingMethod)({{ $order->shippingMethod->name }})@endif</dt>
            <dd>{{ number_format($order->shipping_amount) }} تومان</dd>
        </div>
        <div class="admin-order__totals-row admin-order__totals-row--final">
            <dt>مبلغ نهایی</dt>
            <dd>{{ number_format($order->final_amount) }} تومان</dd>
        </div>
        @if($order->paidAmount() > 0)
            <div class="admin-order__totals-row">
                <dt>پرداخت‌شده</dt>
                <dd>{{ number_format($order->paidAmount()) }} تومان</dd>
            </div>
        @endif
        @if($order->remainingAmount() > 0 && $order->payment_method === 'online')
            <div class="admin-order__totals-row">
                <dt>مانده</dt>
                <dd>{{ number_format($order->remainingAmount()) }} تومان</dd>
            </div>
        @endif
    </dl>
</section>
