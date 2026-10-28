@php
    $isRepApp = request()->routeIs('filament.representative.*') && ! request()->routeIs('filament.representative.auth.*');
@endphp
@if ($isRepApp && auth()->check())
    <link rel="stylesheet" href="{{ asset('representative/introjs.min.css') }}?v=7.2.0">
    <link rel="stylesheet" href="{{ asset('representative/rep-panel-tours.css') }}?v={{ @filemtime(public_path('representative/rep-panel-tours.css')) ?: 1 }}">
@endif
