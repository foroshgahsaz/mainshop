<div>
    <button type="button" wire:click="closeModal"
            class="absolute top-4 left-4 text-gray-400 hover:text-gray-600 z-10"
            aria-label="بستن">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 6 6 18M6 6l12 12" />
        </svg>
    </button>

    @include('livewire.auth.login-panel')
</div>
