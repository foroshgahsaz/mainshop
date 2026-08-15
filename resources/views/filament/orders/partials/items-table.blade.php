<section class="admin-order__section admin-order__section--items">
    <h2 class="admin-order__section-title">اقلام سفارش</h2>
    <div class="admin-order__table-wrap">
        <table class="admin-order__table">
            <thead>
                <tr>
                    <th>محصول</th>
                    <th>SKU</th>
                    <th>قیمت واحد</th>
                    <th>تعداد</th>
                    <th>جمع</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr>
                        <td>
                            <span class="admin-order__product-name">{{ $item->product_name }}</span>
                            @if($item->product)
                                <a href="{{ route('products.show', $item->product) }}" target="_blank" class="admin-order__link">مشاهده در فروشگاه</a>
                            @endif
                        </td>
                        <td dir="ltr">{{ $item->sku ?? '—' }}</td>
                        <td>{{ number_format($item->price) }} تومان</td>
                        <td>{{ $item->quantity }}</td>
                        <td><strong>{{ number_format($item->total_price) }} تومان</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="admin-order__empty">آیتمی ثبت نشده</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
