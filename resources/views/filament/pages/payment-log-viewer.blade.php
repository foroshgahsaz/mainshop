<x-filament-panels::page>
    <div class="admin-payment-log-viewer" data-admin-payment-log>
        <section class="admin-order__section">
            <h2 class="admin-order__section-title">جستجو در فایل لاگ</h2>
            <p class="admin-order__hint">
                فقط خواندنی — منبع: <code dir="ltr">storage/logs/payments-YYYY-MM-DD.log</code>
                (یا <code dir="ltr">payments.log</code> در صورت نبود فایل روزانه).
            </p>

            <form wire:submit="search" class="admin-payment-log-viewer__form">
                <div class="admin-payment-log-viewer__fields">
                    <div class="admin-payment-log-viewer__field">
                        <label class="admin-order__label" for="paymentLogTracking">کد رهگیری پرداخت</label>
                        <input id="paymentLogTracking"
                               type="search"
                               class="admin-order__textarea admin-payment-log-viewer__input"
                               dir="ltr"
                               wire:model="trackingCode"
                               placeholder="مثال: AB12CD34EF56"
                               autocomplete="off"
                               required>
                        @error('trackingCode')
                            <p class="admin-order__error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="admin-payment-log-viewer__field">
                        <label class="admin-order__label" for="paymentLogDate">تاریخ فایل لاگ</label>
                        <select id="paymentLogDate" class="admin-order__textarea admin-payment-log-viewer__input" wire:model="logDate">
                            @foreach ($this->logDateOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="admin-payment-log-viewer__actions">
                    <x-filament::button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="search">نمایش رکوردها</span>
                        <span wire:loading wire:target="search">در حال خواندن فایل…</span>
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" wire:click="searchAllRecentDays">
                        جستجو در ۷ روز اخیر
                    </x-filament::button>
                </div>
            </form>

            @if ($paymentUrl)
                <p class="admin-payment-log-viewer__link-wrap">
                    <a href="{{ $paymentUrl }}" class="admin-order__link">مشاهده پرداخت در ادمین (یادداشت‌های ۰–۱۰۰)</a>
                </p>
            @endif
        </section>

        @if ($searchMessage)
            <p class="admin-order__empty admin-payment-log-viewer__empty">{{ $searchMessage }}</p>
        @endif

        @if ($scannedFiles !== [])
            <p class="admin-payment-log-viewer__meta" dir="ltr">
                فایل‌های اسکن‌شده:
                @foreach ($scannedFiles as $file)
                    <code>{{ $file }}</code>@if (! $loop->last)، @endif
                @endforeach
            </p>
        @endif

        @if ($truncated)
            <p class="admin-order__hint admin-payment-log-viewer__warn">
                بیش از ۸۰۰ خط هم‌خوان پیدا شد؛ فقط بخش اول نمایش داده شده است.
            </p>
        @endif

        @if ($logLines !== [])
            <section class="admin-order__section">
                <h2 class="admin-order__section-title">
                    {{ count($logLines) }} رکورد برای <span dir="ltr">{{ $trackingCode }}</span>
                </h2>
                <pre class="admin-payment-log-viewer__pre" dir="ltr">@foreach ($logLines as $line){{ $line }}
@endforeach</pre>
            </section>
        @endif
    </div>
</x-filament-panels::page>
