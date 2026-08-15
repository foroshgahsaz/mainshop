<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_START) }}

    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @if ($favicon = filament()->getFavicon())
        <link rel="icon" href="{{ $favicon }}">
    @endif

    <title>ورود - {{ filament()->getBrandName() }}</title>

    @filamentStyles

    <link href="{{ asset('vendor/bootstrap/5.3.0/css/bootstrap.rtl.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/6.4.0/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('adminpanel/login.css') }}">
    <link rel="stylesheet" href="{{ asset('adminpanel/login-filament.css') }}">

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_END) }}
</head>
<body class="fi-body admin-login-page">
    {{ $slot }}

    @filamentScripts(withCore: true)
    <script src="{{ asset('adminpanel/login.js') }}"></script>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_END) }}
</body>
</html>
