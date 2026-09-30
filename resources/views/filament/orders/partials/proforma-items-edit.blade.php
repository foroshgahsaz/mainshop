<section class="admin-order__box admin-order__box--proforma-edit">
    <h3 class="admin-order__box-title">ویرایش اقلام پیش‌فاکتور (ادمین)</h3>
    <p class="admin-order__hint">
        تعداد و تخفیف هر ردیف (مبلغ ثابت یا درصد). تغییرات در یادداشت خصوصی ثبت می‌شود و در PDF به‌صورت ردیف فاکتور نمایش داده می‌شود.
    </p>

    <form wire:submit="saveProformaItems" class="admin-order__proforma-items-form">
        <div class="admin-order__table-wrap">
            <table class="admin-order__table">
                <thead>
                    <tr>
                        <th>محصول</th>
                        <th>قیمت واحد</th>
                        <th>تعداد</th>
                        <th>نوع تخفیف</th>
                        <th>مقدار تخفیف</th>
                        <th>جمع ردیف</th>
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
                            </td>
                            <td>
                                <select class="admin-order__select"
                                        wire:model="editItemDiscountTypes.{{ $item->id }}">
                                    <option value="none">بدون تخفیف</option>
                                    <option value="fixed">مبلغ (تومان)</option>
                                    <option value="percent">درصد</option>
                                </select>
                            </td>
                            <td>
                                <input type="number"
                                       min="0"
                                       max="999999999"
                                       class="admin-order__input admin-order__input--qty"
                                       wire:model="editItemDiscountValues.{{ $item->id }}"
                                       @disabled(($editItemDiscountTypes[$item->id] ?? 'none') === 'none')>
                            </td>
                            <td>{{ number_format($item->total_price) }} تومان</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @error('quantity') <p class="admin-order__error">{{ $message }}</p> @enderror
        @error('discount') <p class="admin-order__error">{{ $message }}</p> @enderror
        @error('order') <p class="admin-order__error">{{ $message }}</p> @enderror

        <button type="submit" class="admin-order__btn admin-order__btn--primary admin-order__btn--block">
            ذخیره اقلام و تخفیف ردیف
        </button>
    </form>
</section>
