<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>انتقال به درگاه تارا</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 4rem 1.5rem; color: #1f2937; }
        p { font-size: 1rem; }
        button { margin-top: 1rem; padding: .75rem 1.5rem; background: #059669; color: #fff; border: 0; border-radius: .75rem; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <p>در حال انتقال به درگاه اعتباری تارا...</p>
    <form id="tara-ipg" method="post" action="{{ $action }}">
        <input type="hidden" name="username" value="{{ $username }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <noscript>
            <button type="submit">ادامه پرداخت</button>
        </noscript>
    </form>
    <script>document.getElementById('tara-ipg').submit();</script>
</body>
</html>
