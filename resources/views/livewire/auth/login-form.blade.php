@php
    $otpResendRemaining = $this->otpResendRemainingSeconds();
    $hasPendingOtp = $this->hasPendingOtpForPhone();
    $otpCooldownBlocksSend = $otpResendRemaining > 0 && ! $hasPendingOtp;
@endphp

@if (session('login_success'))
    <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('login_success') }}</div>
@endif

@if ($step === 'phone')
    <form wire:submit="sendOtp" class="space-y-4">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">شماره موبایل</label>
            <input type="tel" wire:model="phone" dir="ltr"
                   placeholder="09123456789"
                   autofocus
                   @disabled($otpCooldownBlocksSend)
                   class="w-full border-2 border-gray-200 rounded-xl py-3 px-4 text-sm outline-none focus:border-brand-green disabled:bg-gray-50 disabled:text-gray-500">
            @error('phone')
                @if ($otpResendRemaining <= 0)
                    <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span>
                @endif
            @enderror
        </div>

        @if ($otpResendRemaining > 0)
            @include('livewire.auth.otp-resend-timer', [
                'remaining' => $otpResendRemaining,
                'showResendButton' => false,
            ])
        @endif

        @if ($hasPendingOtp && $otpResendRemaining > 0)
            <p class="text-sm text-emerald-700 bg-emerald-50 rounded-xl px-3 py-2 text-center">
                کد قبلاً برای این شماره ارسال شده است. برای وارد کردن کد ادامه دهید.
            </p>
        @endif

        <button type="submit"
                wire:loading.attr="disabled"
                @disabled($otpCooldownBlocksSend)
                class="w-full bg-brand-gold hover:bg-amber-600 text-white font-bold py-3 rounded-xl transition disabled:opacity-60 disabled:cursor-not-allowed">
            @if ($hasPendingOtp && $otpResendRemaining > 0)
                <span wire:loading.remove wire:target="sendOtp">ادامه — وارد کردن کد تایید</span>
            @else
                <span wire:loading.remove wire:target="sendOtp">دریافت کد تایید</span>
            @endif
            <span wire:loading wire:target="sendOtp">در حال ارسال...</span>
        </button>
    </form>
@else
    <form wire:submit="verifyOtp" class="space-y-4">
        <div class="text-sm text-gray-600 bg-gray-50 rounded-xl p-3">
            کد به <strong dir="ltr">{{ $phone }}</strong> ارسال شد.
            <button type="button" wire:click="backToPhone" class="text-brand-green font-bold mr-2">ویرایش</button>
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">کد تایید</label>
            <input type="text" wire:model="otp" dir="ltr" maxlength="6" inputmode="numeric"
                   placeholder="------"
                   autofocus
                   class="w-full border-2 border-gray-200 rounded-xl py-3 px-4 text-center text-lg tracking-[0.5em] outline-none focus:border-brand-green">
            @error('otp') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled"
                class="w-full bg-brand-green hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition">
            <span wire:loading.remove wire:target="verifyOtp">تایید و ورود</span>
            <span wire:loading wire:target="verifyOtp">در حال بررسی...</span>
        </button>

        @include('livewire.auth.otp-resend-timer', [
            'remaining' => $otpResendRemaining,
            'showResendButton' => true,
        ])
    </form>
@endif

<p class="mt-6 text-center text-xs text-gray-400 leading-6">
    با ورود، شرایط استفاده از فروشگاه {{ site_name() }} را می‌پذیرید.
</p>
