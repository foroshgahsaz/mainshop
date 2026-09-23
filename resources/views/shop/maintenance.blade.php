@php
    $trustBadges = app(\App\Services\Settings\TrustBadgeService::class)->active();
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>در حال بروزرسانی | {{ $site['name'] }}</title>
    <link rel="preload" href="{{ asset('fonts/yekan/fonts.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: 'YekanBakh', Tahoma, sans-serif;
            background: #ffffff;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 36rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 40px -12px rgba(15, 23, 42, 0.12);
        }
        .icon {
            width: 4.5rem;
            height: 4.5rem;
            margin: 0 auto 1.25rem;
            border-radius: 9999px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon svg { width: 2.25rem; height: 2.25rem; color: #059669; }
        .brand {
            font-size: 1.125rem;
            font-weight: 800;
            color: #001a72;
            margin-bottom: 0.75rem;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 900;
            margin-bottom: 1.75rem;
            color: #111827;
        }
        .trust-section {
            margin-top: 0.25rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eef2f7;
        }
        .trust-section__title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 1rem;
        }
        .trust-badges {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .trust-badge {
            width: 6rem;
            height: 6rem;
            flex: 0 0 6rem;
            border-radius: 0.75rem;
            border: 1px dashed #d1d5db;
            background: #fff;
            overflow: hidden;
            box-sizing: border-box;
            padding: 0.35rem;
        }
        .trust-badge__inner {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .trust-badge__code {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            overflow: hidden;
            text-align: center;
            line-height: 1;
            transform: scale(0.72);
            transform-origin: center center;
        }
        .trust-badge__code img,
        .trust-badge__code a,
        .trust-badge__code iframe {
            display: block;
            max-width: 100%;
            max-height: 100%;
            margin: 0 auto;
        }
        .trust-badge__code img {
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .trust-badge__link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        .trust-badge__image {
            display: block;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .contact {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #eef2f7;
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.8;
        }
        .contact a {
            color: #059669;
            text-decoration: none;
        }
        .contact a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>

        <p class="brand">{{ $site['name'] }}</p>
        <h1>سایت در حال بروزرسانی است</h1>

        @if ($trustBadges !== [])
            <section class="trust-section" aria-label="نمادهای اعتماد">
                <p class="trust-section__title">نمادهای اعتماد</p>
                <div class="trust-badges">
                    @include('shop.partials.trust-badge-items', ['trustBadges' => $trustBadges, 'variant' => 'maintenance'])
                </div>
            </section>
        @endif

        @if (! empty($site['phone']) || ! empty($site['email']))
            <div class="contact">
                @if (! empty($site['phone']))
                    <p>پشتیبانی: <a href="tel:{{ $site['phone'] }}">{{ $site['phone'] }}</a></p>
                @endif
                @if (! empty($site['email']))
                    <p>ایمیل: <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></p>
                @endif
            </div>
        @endif
    </main>
</body>
</html>
