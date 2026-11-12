@if ($existingCustomerMessage)
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100" wire:key="customer-transfer-box">
        <p class="font-medium leading-7">{{ $existingCustomerMessage }}</p>

        @if ($canTransferExistingCustomer)
            <div class="mt-3 flex flex-wrap items-center gap-2">
                @unless ($transferOtpSent)
                    <x-filament::button
                        color="warning"
                        wire:click="requestCustomerTransfer"
                        wire:loading.attr="disabled"
                    >
                        انتقال مشتری به خود
                    </x-filament::button>
                @else
                    <div class="w-full space-y-3">
                        <p class="text-xs leading-6 opacity-90">کد تأیید برای موبایل مشتری ارسال شد. پس از وارد کردن کد، مشتری به نمایندگی شما منتقل می‌شود.</p>
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="min-w-[10rem] flex-1">
                                <label class="mb-1 block text-xs font-medium" for="transfer-otp-input">کد تأیید</label>
                                <input
                                    id="transfer-otp-input"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900"
                                    wire:model="transferOtp"
                                    autocomplete="one-time-code"
                                />
                            </div>
                            <x-filament::button color="success" wire:click="confirmCustomerTransfer" wire:loading.attr="disabled">
                                تأیید و انتقال
                            </x-filament::button>
                            <x-filament::button color="gray" wire:click="requestCustomerTransfer" wire:loading.attr="disabled">
                                ارسال مجدد کد
                            </x-filament::button>
                        </div>
                    </div>
                @endunless
            </div>
        @endif
    </div>
@endif
