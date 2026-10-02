@include('filament.hooks.critical-admin-sidebar')
@php
    $adminAssetV = max(
        @filemtime(public_path('adminpanel/styles.css')) ?: 0,
        @filemtime(public_path('adminpanel/filament-overrides.css')) ?: 0,
    );
@endphp
<link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
<link href="{{ asset('vendor/bootstrap/5.3.0/css/bootstrap.rtl.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/fontawesome/6.4.0/css/all.min.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('adminpanel/styles.css') }}?v={{ $adminAssetV }}">
@if (request()->routeIs('filament.admin.pages.dashboard'))
    @include('filament.hooks.dashboard-map-styles')
@endif
<link rel="stylesheet" href="{{ asset('vendor/sweetalert2/11/sweetalert2.min.css') }}">
<link rel="stylesheet" href="{{ asset('adminpanel/filament-overrides.css') }}?v={{ $adminAssetV }}">
<link rel="stylesheet" href="{{ asset('css/filament-admin-order.css') }}">
