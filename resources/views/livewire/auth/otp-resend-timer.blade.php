@props([
    'remaining' => 0,
    'wireAction' => 'sendOtp',
    'wireTarget' => 'sendOtp',
    'showResendButton' => true,
])

<div
    class="otp-resend-timer text-center"
    wire:key="otp-resend-timer-{{ $remaining }}-{{ $wireAction }}"
    x-data="{
        remaining: {{ max(0, (int) $remaining) }},
        interval: null,
        get clock() {
            const minutes = String(Math.floor(this.remaining / 60)).padStart(2, '0');
            const seconds = String(this.remaining % 60).padStart(2, '0');
            return minutes + ':' + seconds;
        },
        start() {
            if (this.interval) {
                clearInterval(this.interval);
            }
            if (this.remaining <= 0) {
                return;
            }
            this.interval = setInterval(() => {
                if (this.remaining <= 0) {
                    clearInterval(this.interval);
                    return;
                }
                this.remaining -= 1;
            }, 1000);
        }
    }"
    x-init="start()"
>
    <p x-show="remaining > 0" class="text-sm text-amber-600 font-medium py-1" x-cloak>
        ارسال مجدد تا <span dir="ltr" class="font-bold" x-text="clock"></span>
    </p>
    @if ($showResendButton)
        <button
            type="button"
            x-show="remaining <= 0"
            x-cloak
            wire:click="{{ $wireAction }}"
            wire:loading.attr="disabled"
            wire:target="{{ $wireTarget }}"
            class="w-full text-sm text-brand-green hover:text-emerald-700 py-2 font-bold"
        >
            ارسال مجدد کد
        </button>
    @endif
</div>
