@props([
    'targets',
    'message' => 'در حال بارگذاری…',
    'hint' => 'تا آماده شدن مرحله بعد کلیک نکنید.',
])

@teleport('body')
    <div wire:loading.flex
         wire:target="{{ $targets }}"
         {{ $attributes->class(['rep-modal-backdrop', 'rep-modal-backdrop--preparing', 'rep-wizard-busy-overlay']) }}
         role="status"
         aria-live="polite"
         aria-busy="true">
        <div class="rep-modal rep-modal--compact">
            <div class="rep-modal__spinner" aria-hidden="true"></div>
            <p class="rep-modal__status">{{ $message }}</p>
            <p class="rep-modal__status rep-modal__status--hint">{{ $hint }}</p>
        </div>
    </div>
@endteleport