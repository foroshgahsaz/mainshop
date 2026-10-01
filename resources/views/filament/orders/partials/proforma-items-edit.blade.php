<section class="admin-order__box admin-order__box--proforma-edit">
    <h3 class="admin-order__box-title">ویرایش اقلام پیش‌فاکتور (ادمین)</h3>
    <p class="admin-order__hint">
        تعداد را در جدول تنظیم کنید؛ تخفیف هر ردیف از پاپ‌آپ بلافاصله ذخیره می‌شود (دکمه «ویرایش تخفیف»).
    </p>

    <form wire:submit="saveProformaItems" class="admin-order__proforma-items-form">
        <div class="admin-order__table-wrap">
            <table class="admin-order__table">
                <thead>
                    <tr>
                        <th>محصول</th>
                        <th>قیمت واحد</th>
                        <th>تعداد</th>
                        <th>تخفیف</th>
                        <th>جمع ردیف</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        @php
                            $dType = $editItemDiscountTypes[$item->id] ?? 'none';
                            $dVal = (int) ($editItemDiscountValues[$item->id] ?? 0);
                            $dLabel = match ($dType) {
                                'fixed' => number_format($dVal).' تومان',
                                'percent' => $dVal.'٪',
                                default => '—',
                            };
                        @endphp
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
                                <span class="admin-order__discount-pill">{{ $dLabel }}</span>
                                <button type="button"
                                        class="admin-order__btn admin-order__btn--secondary admin-order__btn--compact"
                                        wire:click="openItemDiscountModal({{ $item->id }})">
                                    {{ $dType === 'none' ? 'تخفیف ردیف' : 'ویرایش تخفیف' }}
                                </button>
                            </td>
                            <td>{{ number_format($this->previewItemLineTotal($item->id)) }} تومان</td>
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
