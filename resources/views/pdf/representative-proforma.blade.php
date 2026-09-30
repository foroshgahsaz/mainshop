<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>پیش‌فاکتور {{ $order->tracking_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; line-height: 1.5; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .muted { color: #555; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background: #f3f4f6; }
        .totals { margin-top: 16px; width: 100%; }
        .totals td { border: none; padding: 4px 0; }
        .totals .label { text-align: right; width: 70%; }
        .totals .value { text-align: left; font-weight: bold; }
        .final { font-size: 14px; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>پیش‌فاکتور</h1>
    <p class="muted">{{ $siteName }} — کد: {{ $order->tracking_code }}</p>
    <p class="muted">تاریخ: {{ $order->updated_at?->format('Y/m/d H:i') }}</p>

    <p><strong>مشتری:</strong> {{ $order->user?->name }} — {{ $order->user?->phone }}</p>
    @if ($order->address)
        <p><strong>آدرس:</strong> {{ $order->address->address }}
            @if ($order->address->cityModel?->name ?? $order->address->city)
                — {{ $order->address->cityModel?->name ?? $order->address->city }}
            @endif
            @if ($order->address->provinceModel?->name ?? $order->address->province)
                ، {{ $order->address->provinceModel?->name ?? $order->address->province }}
            @endif
        </p>
    @endif
    @if ($order->freightCarrier)
        <p><strong>باربری:</strong> {{ $order->freightCarrier->displayLabel() }}</p>
    @endif
    <p><strong>درگاه پرداخت:</strong> {{ \App\Support\ShopLabels::paymentMethod($order->payment_method) }}</p>

    <table>
        <thead>
            <tr>
                <th>ردیف</th>
                <th>شرح کالا</th>
                <th>تعداد</th>
                <th>فی (تومان)</th>
                <th>جمع (تومان)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format((int) $item->price) }}</td>
                    <td>{{ number_format((int) $item->total_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">جمع اقلام:</td>
            <td class="value">{{ number_format((int) $order->total_amount) }} تومان</td>
        </tr>
        <tr>
            <td class="label final">مبلغ نهایی:</td>
            <td class="value final">{{ number_format((int) $order->final_amount) }} تومان</td>
        </tr>
    </table>
</body>
</html>
