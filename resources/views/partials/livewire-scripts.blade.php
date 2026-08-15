@php
    $manifestPath = public_path('vendor/livewire/manifest.json');
    $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
    $livewireVersion = $manifest['/livewire.js'] ?? 'dev';
@endphp

<script
    src="{{ asset('vendor/livewire/livewire.js') }}?id={{ $livewireVersion }}"
    data-csrf="{{ csrf_token() }}"
    data-update-uri="{{ url('/livewire/update') }}"
    data-navigate-once="true"
></script>
