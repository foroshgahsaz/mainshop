@php
    $panel = filament()->getCurrentPanel();
    $isRepPanel = $panel?->getId() === 'representative';
@endphp
@if ($isRepPanel && auth()->check())
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('representative/rep-panel-tours.css') }}?v={{ @filemtime(public_path('representative/rep-panel-tours.css')) ?: 1 }}">
@endif
