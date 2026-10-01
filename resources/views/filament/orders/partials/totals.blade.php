<section class="admin-order__section admin-order__section--totals">
    <h2 class="admin-order__section-title">خلاصه مبالغ</h2>
    @php
        $discountLines = \App\Support\Order\OrderInvoiceSummaryBreakdown::discountLines($order);
        $feeLines = \App\Support\Order\OrderInvoiceSummaryBreakdown::feeLines($order);
    @endphp
    <dl class="admin-order__totals">
        <div class="admin-order__totals-row">
            <dt>جمع اقلام (قبل از تخفیف)</dt>
            <dd>{{ number_format($order->total_amount) }} تومان</dd>
        </div>
        @foreach($discountLines as $discountLine)
            <div class="admin-order__totals-row admin-order__totals-row--discount admin-order__totals-row--indented">
                <dt>{{ $discountLine['label'] }}</dt>
                <dd>−{{ number_format($discountLine['amount']) }} تومان</dd>
            </div>
        @endforeach
        @if($order->discount_amount > 0 && $discountLines === [])
            <div class="admin-order__totals-row admin-order__totals-row--discount">
                <dt>تخفیف</dt>
                <dd>−{{ number_format($order->discount_amount) }} تومان</dd>
            </div>
        @endif
        @foreach($feeLines as $feeLine)
            <div class="admin-order__totals-row admin-order__totals-row--indented">
                <dt>{{ $feeLine['label'] }}</dt>
                <dd>+{{ number_format($feeLine['amount']) }} تومان</dd>
            </div>
        @endforeach
        <div class="admin-order__totals-row">
            <dt>هزینه ارسال @if($order->shippingMethod)({{ $order->shippingMethod->name }})@endif</dt>
            <dd>{{ number_format($order->shipping_amount) }} تومان</dd>
        </div>
        <div class="admin-order__totals-row admin-order__totals-row--final">
            <dt>مبلغ نهایی فاکتور</dt>
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
