@php
    $isRepApp = request()->routeIs('filament.representative.*') && ! request()->routeIs('filament.representative.auth.*');
@endphp
@if ($isRepApp && auth()->check())
    <link rel="stylesheet" href="{{ asset('rep-panel/introjs.min.css') }}?v=7.2.0">
    <link rel="stylesheet" href="{{ asset('rep-panel/rep-panel-tours.css') }}?v={{ @filemtime(public_path('rep-panel/rep-panel-tours.css')) ?: 1 }}">
@endif
