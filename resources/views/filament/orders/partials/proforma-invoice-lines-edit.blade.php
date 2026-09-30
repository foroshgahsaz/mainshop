<section class="admin-order__box admin-order__box--proforma-edit">
    <h3 class="admin-order__box-title">ردیف‌های اضافه فاکتور (ادمین)</h3>
    <p class="admin-order__hint">
        تخفیف کل سفارش یا هزینه‌های اضافه (مبالغ مثبت). هر ردیف در جدول پیش‌فاکتور / PDF با عنوان دلخواه نمایش داده می‌شود.
    </p>

    <form wire:submit="saveInvoiceLines" class="admin-order__proforma-items-form">
        <div class="admin-order__invoice-lines">
            @forelse($editInvoiceLines as $index => $line)
                <div class="admin-order__invoice-line-row" wire:key="invoice-line-{{ $index }}-{{ $line['id'] ?? 'new' }}">
                    <select class="admin-order__select" wire:model="editInvoiceLines.{{ $index }}.kind">
                        <option value="order_discount">تخفیف کل</option>
                        <option value="fee">هزینه / اضافه</option>
                    </select>
                    <input type="text"
                           class="admin-order__input"
                           placeholder="شرح (مثلاً تخفیف ویژه)"
                           wire:model="editInvoiceLines.{{ $index }}.title">
                    <input type="number"
                           min="1"
                           class="admin-order__input admin-order__input--qty admin-order__input--amount"
                           placeholder="مبلغ"
                           wire:model="editInvoiceLines.{{ $index }}.amount">
                    <button type="button"
                            class="admin-order__btn admin-order__btn--ghost"
                            wire:click="removeInvoiceLineRow({{ $index }})">
                        حذف
                    </button>
                </div>
            @empty
                <p class="admin-order__hint">ردیف اضافه‌ای ثبت نشده است.</p>
            @endforelse
        </div>

        <div class="admin-order__invoice-line-actions">
            <button type="button"
                    class="admin-order__btn admin-order__btn--secondary"
                    wire:click="addInvoiceLineRow">
                افزودن ردیف
            </button>
            <button type="submit" class="admin-order__btn admin-order__btn--primary">
                ذخیره ردیف‌های فاکتور
            </button>
        </div>

        @error('invoice_lines') <p class="admin-order__error">{{ $message }}</p> @enderror
    </form>
</section>
