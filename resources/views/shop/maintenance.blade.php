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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #0f766e 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 32rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            text-align: center;
            backdrop-filter: blur(12px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
        }
        .icon {
            width: 5rem;
            height: 5rem;
            margin: 0 auto 1.5rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #10b981, #14b8a6);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2.4s ease-in-out infinite;
        }
        .icon svg { width: 2.5rem; height: 2.5rem; color: #fff; }
        h1 {
            font-size: 1.75rem;
            font-weight: 900;
            margin-bottom: 0.75rem;
            color: #fff;
        }
        .brand {
            font-size: 1.125rem;
            font-weight: 700;
            color: #6ee7b7;
            margin-bottom: 1.25rem;
        }
        .message {
            font-size: 1rem;
            line-height: 1.9;
            color: #cbd5e1;
            margin-bottom: 1.75rem;
        }
        .contact {
            font-size: 0.875rem;
            color: #94a3b8;
            line-height: 1.8;
        }
        .contact a {
            color: #6ee7b7;
            text-decoration: none;
        }
        .contact a:hover { text-decoration: underline; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }
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
        <p class="message">{{ $message }}</p>

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
