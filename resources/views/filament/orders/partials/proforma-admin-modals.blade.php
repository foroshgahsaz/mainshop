@if ($showInvoiceLineModal)
    <div class="admin-order-modal-backdrop" wire:click="closeInvoiceLineModal">
        <div class="admin-order-modal" role="dialog" aria-modal="true" wire:click.stop>
            <h3 class="admin-order-modal__title">
                {{ $invoiceLineModalIndex !== null ? 'ویرایش ردیف فاکتور' : 'افزودن تخفیف یا هزینه' }}
            </h3>

            <div class="admin-order-modal__choices">
                <label class="admin-order-modal__choice">
                    <input type="radio" wire:model.live="modalInvoiceLineKind" value="order_discount">
                    <span>تخفیف کل (کسر از فاکتور)</span>
                </label>
                <label class="admin-order-modal__choice">
                    <input type="radio" wire:model.live="modalInvoiceLineKind" value="fee">
                    <span>هزینه / اضافه (مبلغ مثبت)</span>
                </label>
            </div>

            <div class="admin-order__field">
                <label class="admin-order__label">شرح روی فاکتور</label>
                <input type="text" class="admin-order__input admin-order__input--full" wire:model="modalInvoiceLineTitle" placeholder="مثلاً تخفیف ویژه یا هزینه تخلیه">
                @error('modalInvoiceLineTitle') <p class="admin-order__error">{{ $message }}</p> @enderror
            </div>

            <div class="admin-order__field">
                <label class="admin-order__label">مبلغ (تومان)</label>
                <input type="number" min="1" class="admin-order__input admin-order__input--full" wire:model="modalInvoiceLineAmount">
                @error('modalInvoiceLineAmount') <p class="admin-order__error">{{ $message }}</p> @enderror
            </div>

            <div class="admin-order-modal__actions">
                <button type="button" class="admin-order__btn admin-order__btn--ghost" wire:click="closeInvoiceLineModal">انصراف</button>
                <button type="button" class="admin-order__btn admin-order__btn--primary" wire:click="confirmInvoiceLineModal">تأیید</button>
            </div>
        </div>
    </div>
@endif

@if ($showItemDiscountModal)
    <div class="admin-order-modal-backdrop" wire:click="closeItemDiscountModal">
        <div class="admin-order-modal" role="dialog" aria-modal="true" wire:click.stop>
            <h3 class="admin-order-modal__title">تخفیف ردیف کالا</h3>

            <div class="admin-order-modal__choices admin-order-modal__choices--stack">
                <label class="admin-order-modal__choice">
                    <input type="radio" wire:model.live="modalItemDiscountType" value="none">
                    <span>بدون تخفیف</span>
                </label>
                <label class="admin-order-modal__choice">
                    <input type="radio" wire:model.live="modalItemDiscountType" value="fixed">
                    <span>مبلغ ثابت (تومان) از جمع این ردیف</span>
                </label>
                <label class="admin-order-modal__choice">
                    <input type="radio" wire:model.live="modalItemDiscountType" value="percent">
                    <span>درصد از جمع این ردیف</span>
                </label>
            </div>

            @if ($modalItemDiscountType !== 'none')
                <div class="admin-order__field">
                    <label class="admin-order__label">
                        {{ $modalItemDiscountType === 'percent' ? 'درصد (۱ تا ۱۰۰)' : 'مبلغ تخفیف (تومان)' }}
                    </label>
                    <input type="number"
                           min="1"
                           @if($modalItemDiscountType === 'percent') max="100" @endif
                           class="admin-order__input admin-order__input--full"
                           wire:model="modalItemDiscountValue">
                    @error('modalItemDiscountValue') <p class="admin-order__error">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="admin-order-modal__actions">
                <button type="button" class="admin-order__btn admin-order__btn--ghost" wire:click="closeItemDiscountModal">انصراف</button>
                <button type="button" class="admin-order__btn admin-order__btn--primary" wire:click="confirmItemDiscountModal">تأیید</button>
            </div>
        </div>
    </div>
@endif
