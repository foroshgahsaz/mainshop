<script src="{{ asset('js/livewire-session-guard.js') }}?v={{ @filemtime(public_path('js/livewire-session-guard.js')) ?: 0 }}"
        data-ping-url="{{ route('session.ping') }}"></script>
