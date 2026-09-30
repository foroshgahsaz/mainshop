<section class="admin-order__box admin-order__box--actions">
    <h3 class="admin-order__box-title">مدیریت سفارش</h3>
    <form wire:submit="saveOrderMeta" class="admin-order__actions-form">
        <div class="admin-order__field">
            <label class="admin-order__label">وضعیت سفارش</label>
            <select wire:model="editStatus" class="admin-order__select admin-order__select--full">
                @foreach([
                    'draft' => 'پیش‌نویس نماینده',
                    'proforma' => 'پیش‌فاکتور نماینده',
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

        @if($order->isRepresentativeOrder() && ($order->isDraft() || $order->isProforma()))
            <div class="admin-order__field">
                <label class="admin-order__label">باربری (نمایندگی)</label>
                <select wire:model="editFreightCarrierId" class="admin-order__select admin-order__select--full">
                    <option value="">— انتخاب —</option>
                    @foreach($this->freightCarrierOptions as $carrier)
                        <option value="{{ $carrier->id }}">{{ $carrier->displayLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-order__field">
                <label class="admin-order__label">درگاه پرداخت</label>
                <select wire:model="editPaymentGateway" class="admin-order__select admin-order__select--full">
                    @foreach($this->paymentGatewayOptions as $gateway)
                        <option value="{{ $gateway['name'] }}">{{ $gateway['label'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <button type="submit" class="admin-order__btn admin-order__btn--primary admin-order__btn--block">
            ذخیره تغییرات
        </button>
    </form>
</section>
