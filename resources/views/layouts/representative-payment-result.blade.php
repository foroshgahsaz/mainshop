<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتیجه پرداخت — {{ site_name() }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('shop/css/tailwind.css') }}">
    <link rel="stylesheet" href="{{ asset('shop/css/custom.css') }}">
    @php
        $paymentResultCss = public_path('shop/css/payment-result.css');
        $paymentResultCssVersion = is_file($paymentResultCss) ? filemtime($paymentResultCss) : 1;
    @endphp
    <link rel="stylesheet" href="{{ asset('shop/css/payment-result.css') }}?v={{ $paymentResultCssVersion }}">
    @include('filament.hooks.styles')
    <style>
        .rep-payment-result-shell { min-height: 100vh; background: #f0fdfa; }
        .rep-payment-result-topbar {
            background: #0d9488;
            color: #fff;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .rep-payment-result-topbar a { color: #fff; text-decoration: underline; }
    </style>
</head>
<body class="rep-payment-result-shell font-yekan text-gray-800">
    <header class="rep-payment-result-topbar">
        <span>پنل نمایندگی — {{ site_name() }}</span>
        <a href="{{ url('/representative') }}">ورود / بازگشت به پنل</a>
    </header>
    <main class="container mx-auto px-4 py-8 max-w-2xl">
        @yield('content')
    </main>
</body>
</html>
