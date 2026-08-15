<section class="admin-order__box admin-order__box--actions">
    <h3 class="admin-order__box-title">مدیریت سفارش</h3>
    <form wire:submit="saveOrderMeta" class="admin-order__actions-form">
        <div class="admin-order__field">
            <label class="admin-order__label">وضعیت سفارش</label>
            <select wire:model="editStatus" class="admin-order__select admin-order__select--full">
                @foreach([
                    'pending' => 'در انتظار',
                    'processing' => 'در حال پردازش',
                    'shipped' => 'ارسال شده',
                    'delivered' => 'تحویل شده',
                    'canceled' => 'لغو شده',
                    'returned' => 'مرجوعی',
                ] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-order__field">
            <label class="admin-order__label">کد رهگیری پست</label>
            <input type="text" wire:model="editTracking" class="admin-order__input" dir="ltr" placeholder="POST-...">
        </div>

        <button type="submit" class="admin-order__btn admin-order__btn--primary admin-order__btn--block">
            ذخیره تغییرات
        </button>
    </form>
</section>
