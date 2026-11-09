<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        use App\Support\ShopAsset;
        use App\Support\ShopLabels;

        $siteSettings = app(\App\Services\Settings\SettingsService::class)->site();
        $needsListingAssets = request()->routeIs('products.*', 'categories.*', 'brands.*');
        $needsSwiper = request()->routeIs('home', 'products.show') || $needsListingAssets;
    @endphp
    <script>
        window.__shopPrice = {
            hidden: @json(ShopLabels::shopPricesHidden()),
            currency: 'ریال',
            factor: 10,
        };
    </script>
    <title>@yield('title', site_name())</title>
    @hasSection('meta')
        @yield('meta')
    @else
        <x-seo-meta :seo="\App\Support\SeoPresenter::for(null, [
            'title' => site_name(),
            'description' => $siteSettings['description'] ?? 'فروشگاه آنلاین',
        ])" />
    @endif
    @stack('meta')
    @if (! empty($siteSettings['favicon']))
        <link rel="icon" href="{{ \App\Support\ShopMedia::url($siteSettings['favicon']) }}">
    @endif
    <link rel="preload" href="{{ asset('fonts/yekan/fonts.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    <link rel="stylesheet" href="{{ ShopAsset::url('shop/css/tailwind.css', 'tailwind_css') }}">
    @if ($needsSwiper)
        <link rel="stylesheet" href="{{ asset('shop/vendor/swiper/swiper-bundle.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ ShopAsset::url('shop/css/custom.css', 'custom_css') }}">
    <link rel="stylesheet" href="{{ ShopAsset::url('shop/css/rich-content.css', 'rich_content_css') }}">
    @if ($needsListingAssets)
        <link rel="stylesheet" href="{{ ShopAsset::url('shop/css/listing.css', 'listing_css') }}">
    @endif
    @auth
        @if(\App\Support\ShopStaffPreview::viewerIsStaff())
            <link rel="stylesheet" href="{{ asset('vendor/fontawesome/6.4.0/css/all.min.css') }}">
        @endif
    @endauth
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
    @livewireStyles
    @include('shop.partials.site-header-snippets')
</head>
<body class="bg-white min-h-screen font-yekan text-gray-800 has-mobile-bottom-nav @if(auth()->check() && \App\Support\ShopStaffPreview::viewerIsStaff()) has-admin-bar @endif @yield('body_class')">

    @if(auth()->check() && \App\Support\ShopStaffPreview::viewerIsStaff())
        @include('shop.partials.admin-bar')
    @endif

    @include('shop.partials.cart-sidebar-shell')

    <div id="shopAppShell" class="shop-app-shell">

    @include('shop.partials.header')

    <main>
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endif
    </main>

    @include('shop.partials.footer')
    </div>

    @include('shop.partials.mobile-bottom-nav')

    @include('shop.partials.overlays')
    @include('shop.partials.mobile-menu')

    @if (auth()->guest() || (auth()->check() && ! \App\Support\UserDisplayName::isShopProfileComplete(auth()->user())))
        @include('shop.partials.login-modal-shell')
    @endif

    @if ($needsSwiper)
        <script src="{{ asset('shop/vendor/swiper/swiper-bundle.min.js') }}" defer></script>
    @endif
    <script src="{{ ShopAsset::url('shop/js/main.js', 'main_js') }}" defer></script>
    @if ($needsListingAssets)
        <script src="{{ ShopAsset::url('shop/js/listing-filters.js', 'listing_filters_js') }}" defer></script>
    @endif
    @stack('scripts')
    @include('partials.livewire-scripts')
    @include('partials.session-guard')
</body>
</html>
