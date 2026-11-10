<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأیید پرداخت داریک</title>
    <style>
        :root { color-scheme: light; }
        body { font-family: system-ui, sans-serif; background: #f8fafc; margin: 0; padding: 2rem 1rem; color: #0f172a; }
        .card { max-width: 28rem; margin: 0 auto; background: #fff; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 8px 30px rgba(15, 23, 42, .08); }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { margin: .25rem 0; line-height: 1.7; color: #475569; font-size: .95rem; }
        .amount { font-weight: 700; color: #0f172a; }
        label { display: block; margin: 1.25rem 0 .35rem; font-weight: 600; }
        input[type=text] { width: 100%; box-sizing: border-box; padding: .75rem 1rem; font-size: 1.25rem; letter-spacing: .35em; text-align: center; border: 1px solid #cbd5e1; border-radius: .75rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
        button, .btn { border: 0; border-radius: .75rem; padding: .65rem 1.1rem; font-weight: 700; cursor: pointer; font-size: .95rem; text-decoration: none; display: inline-block; }
        .btn-primary { background: #7c3aed; color: #fff; flex: 1; }
        .btn-muted { background: #e2e8f0; color: #334155; }
        .btn-link { background: transparent; color: #7c3aed; padding: 0; }
        .flash { padding: .65rem .85rem; border-radius: .65rem; margin-bottom: 1rem; font-size: .9rem; }
        .flash-error { background: #fef2f2; color: #b91c1c; }
        .flash-success { background: #ecfdf5; color: #047857; }
    </style>
</head>
<body>
    <div class="card">
        <h1>پرداخت اعتباری داریک</h1>
        <p>رمز ۵ رقمی خرید به موبایل مشتری ارسال شده است. برای تکمیل پرداخت آن را وارد کنید.</p>
        @if($order)
            <p class="amount">مبلغ: {{ number_format($payment->amount) }} تومان</p>
        @endif

        @if(session('error'))
            <div class="flash flash-error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="flash flash-success">{{ session('success') }}</div>
        @endif

        <form method="post" action="{{ $confirmUrl }}">
            @csrf
            <label for="darik-otp">رمز خرید (OTP)</label>
            <input id="darik-otp" name="otp" type="text" inputmode="numeric" pattern="\d{5}" maxlength="5" autocomplete="one-time-code" required autofocus>
            <div class="actions">
                <button type="submit" class="btn-primary">تأیید و پرداخت</button>
            </div>
        </form>

        <div class="actions">
            <form method="post" action="{{ $resendUrl }}">
                @csrf
                <button type="submit" class="btn-muted">ارسال مجدد رمز</button>
            </form>
            <form method="post" action="{{ $cancelUrl }}">
                @csrf
                <button type="submit" class="btn-muted">انصراف</button>
            </form>
        </div>
    </div>
</body>
</html>
