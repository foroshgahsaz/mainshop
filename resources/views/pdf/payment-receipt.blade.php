<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
        th { background: #f3f4f6; width: 35%; }
        .muted { color: #666; font-size: 11px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>{{ $receipt['title'] }}</h1>
    <p>{{ $receipt['site_name'] }}</p>

    <table>
        <tr><th>درگاه</th><td>{{ $receipt['gateway_label'] }}</td></tr>
        <tr><th>کد پیگیری پرداخت</th><td dir="ltr">{{ $receipt['payment_tracking'] }}</td></tr>
        @if ($receipt['gateway_reference'])
            <tr><th>شناسه مرجع درگاه</th><td dir="ltr">{{ $receipt['gateway_reference'] }}</td></tr>
        @endif
        <tr><th>مبلغ پرداخت</th><td>{{ number_format($receipt['amount']) }} تومان</td></tr>
        @if ($receipt['paid_at'])
            <tr><th>زمان ثبت</th><td>{{ $receipt['paid_at'] }}</td></tr>
        @endif
        @if ($receipt['order_tracking'])
            <tr><th>کد سفارش</th><td dir="ltr">{{ $receipt['order_tracking'] }}</td></tr>
        @endif
        @if ($receipt['customer_name'])
            <tr><th>مشتری</th><td>{{ $receipt['customer_name'] }} @if($receipt['customer_phone']) ({{ $receipt['customer_phone'] }}) @endif</td></tr>
        @endif
        @if ($receipt['representative_name'])
            <tr><th>نماینده</th><td>{{ $receipt['representative_name'] }}</td></tr>
        @endif
    </table>

    <p class="muted">این رسید به‌صورت سیستمی صادر شده است.</p>
</body>
</html>
