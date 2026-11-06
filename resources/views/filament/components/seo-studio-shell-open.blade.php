@php
    $variant = $variant ?? 'seo';
    $shellClass = 'seo-studio-ui'.($variant === 'redirect' ? ' ss-redirect' : '');
@endphp
@once
    @php($ssV = @filemtime(public_path('css/seo-studio.css')) ?: 0)
    <link rel="stylesheet" href="{{ asset('css/seo-studio.css') }}?v={{ $ssV }}">
@endonce
<div class="{{ $shellClass }}" dir="rtl">
