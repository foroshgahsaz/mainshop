<section class="admin-order__box admin-order__box--proforma-edit">
    <h3 class="admin-order__box-title">ردیف‌های اضافه فاکتور (ادمین)</h3>
    <p class="admin-order__hint">
        تخفیف کل یا هزینه‌های اضافه با دکمه زیر اضافه می‌شوند و در PDF به‌صورت ردیف جدا نمایش داده می‌شوند.
    </p>

    <form wire:submit="saveInvoiceLines" class="admin-order__proforma-items-form">
        @if (count($editInvoiceLines) > 0)
            <div class="admin-order__table-wrap">
                <table class="admin-order__table">
                    <thead>
                        <tr>
                            <th>نوع</th>
                            <th>شرح</th>
                            <th>مبلغ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($editInvoiceLines as $index => $line)
                            <tr wire:key="invoice-line-summary-{{ $index }}">
                                <td>{{ ($line['kind'] ?? '') === 'order_discount' ? 'تخفیف کل' : 'هزینه' }}</td>
                                <td>{{ $line['title'] ?? '' }}</td>
                                <td>{{ number_format((int) ($line['amount'] ?? 0)) }} تومان</td>
                                <td class="admin-order__row-actions">
                                    <button type="button"
                                            class="admin-order__btn admin-order__btn--ghost admin-order__btn--compact"
                                            wire:click="openInvoiceLineModal({{ $index }})">
                                        ویرایش
                                    </button>
                                    <button type="button"
                                            class="admin-order__btn admin-order__btn--ghost admin-order__btn--compact"
                                            wire:click="removeInvoiceLineRow({{ $index }})">
                                        حذف
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="admin-order__hint">هنوز ردیف تخفیف یا هزینه‌ای ثبت نشده است.</p>
        @endif

        <div class="admin-order__invoice-line-actions">
            <button type="button"
                    class="admin-order__btn admin-order__btn--secondary"
                    wire:click="openInvoiceLineModal">
                افزودن تخفیف یا هزینه
            </button>
            <button type="submit" class="admin-order__btn admin-order__btn--primary">
                ذخیره ردیف‌های فاکتور
            </button>
        </div>

        @error('invoice_lines') <p class="admin-order__error">{{ $message }}</p> @enderror
    </form>
</section>
