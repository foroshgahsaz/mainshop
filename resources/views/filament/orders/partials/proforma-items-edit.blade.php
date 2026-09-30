<section class="admin-order__box admin-order__box--proforma-edit">
    <h3 class="admin-order__box-title">ویرایش اقلام پیش‌فاکتور (ادمین)</h3>
    <p class="admin-order__hint">
        پس از انقضای مهلت رزرو، می‌توانید تعداد اقلام را تغییر دهید. تغییرات در یادداشت‌های سفارش ثبت می‌شود و در PDF پیش‌فاکتور نمایش داده نمی‌شود.
    </p>

    <form wire:submit="saveProformaItemQuantities" class="admin-order__proforma-items-form">
        <div class="admin-order__table-wrap">
            <table class="admin-order__table">
                <thead>
                    <tr>
                        <th>محصول</th>
                        <th>قیمت واحد</th>
                        <th>تعداد</th>
                        <th>جمع فعلی</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr wire:key="proforma-edit-item-{{ $item->id }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ number_format($item->price) }} تومان</td>
                            <td>
                                <input type="number"
                                       min="1"
                                       max="999"
                                       class="admin-order__input admin-order__input--qty"
                                       wire:model="editItemQuantities.{{ $item->id }}">
                                @error('editItemQuantities.'.$item->id)
                                    <p class="admin-order__error">{{ $message }}</p>
                                @enderror
                            </td>
                            <td>{{ number_format($item->total_price) }} تومان</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @error('order') <p class="admin-order__error">{{ $message }}</p> @enderror

        <button type="submit" class="admin-order__btn admin-order__btn--primary admin-order__btn--block">
            ذخیره تعداد اقلام
        </button>
    </form>
</section>
