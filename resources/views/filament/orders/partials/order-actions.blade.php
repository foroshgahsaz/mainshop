<section class="admin-order__box admin-order__box--actions">
    <h3 class="admin-order__box-title">مدیریت سفارش</h3>
    @if(\App\Support\AdminAccess::isAccountantOnly())
        <p class="admin-order__hint" style="margin-bottom:1rem;font-size:0.875rem;color:var(--gray-600);">
            پس از تأیید پرداخت، فقط انتقال به <strong>پردازش انبار</strong> مجاز است.
        </p>
        <p class="admin-order__field">
            <span class="admin-order__label">وضعیت فعلی</span>
            <span class="admin-order__badge admin-order__badge--status admin-order__badge--{{ $order->status }}">
                {{ \App\Support\ShopLabels::orderStatus($order->status) }}
            </span>
        </p>
        @if($order->canAdvanceToWarehouseProcessing())
            <button
                type="button"
                wire:click="advanceOrderToWarehouse"
                wire:confirm="وضعیت سفارش به «پردازش انبار» تغییر می‌کند. ادامه می‌دهید؟"
                class="admin-order__btn admin-order__btn--primary admin-order__btn--block"
            >
                تأیید پرداخت — پردازش انبار
            </button>
        @else
            <p class="admin-order__hint" style="font-size:0.875rem;color:var(--gray-500);">
                انتقال به پردازش انبار برای این سفارش در حال حاضر ممکن نیست (پرداخت یا وضعیت نامناسب).
            </p>
        @endif
    @else
    <form wire:submit="saveOrderMeta" class="admin-order__actions-form">
        <div class="admin-order__field">
            <label class="admin-order__label">وضعیت سفارش</label>
            <select wire:model="editStatus" class="admin-order__select admin-order__select--full">
                @foreach([
                    'draft' => 'پیش‌نویس نماینده',
                    'proforma' => 'پیش‌فاکتور نماینده',
                    'pending' => 'در انتظار',
                    'processing' => 'در حال پردازش',
                    'warehouse_processing' => 'پردازش انبار',
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
    @endif
</section>
