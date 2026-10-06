@php
    /** @var array<string, mixed> $payload */
    $items = $payload['taraInvoiceItemList'] ?? [];
    $item = is_array($items[0] ?? null) ? $items[0] : [];
@endphp
<section class="admin-order__section admin-tara-payload">
    <h2 class="admin-order__section-title">پیش‌نمایش ارسال به تارا (getToken)</h2>
    @if (! empty($payload['is_preview']))
        <p class="admin-tara-payload__hint">هنوز رکورد پرداخت ایجاد نشده — مقادیر زیر بر اساس مبلغ نهایی سفارش و تنظیمات درگاه محاسبه شده‌اند.</p>
    @else
        <p class="admin-tara-payload__hint">همین مقادیر (به‌ویژه <strong>group</strong> و <strong>groupTitle</strong>) در <code>taraInvoiceItemList</code> قبل از هدایت کاربر به درگاه ارسال می‌شوند.</p>
    @endif

    <dl class="admin-tara-payload__highlights">
        <div>
            <dt>کد گروه کالایی (group)</dt>
            <dd dir="ltr"><code>{{ $payload['group'] ?? $item['group'] ?? '—' }}</code></dd>
        </div>
        <div>
            <dt>نام گروه کالایی (groupTitle)</dt>
            <dd>{{ $payload['groupTitle'] ?? $item['groupTitle'] ?? '—' }}</dd>
        </div>
        <div>
            <dt>مبلغ (تومان)</dt>
            <dd>{{ number_format((int) ($payload['amount_toman'] ?? 0)) }}</dd>
        </div>
        <div>
            <dt>موبایل مشتری</dt>
            <dd dir="ltr">{{ $payload['mobile'] ?? '—' }}</dd>
        </div>
    </dl>

    <details class="admin-tara-payload__details">
        <summary>نمایش کامل آیتم فاکتور تارا</summary>
        <pre class="admin-order__raw-json" dir="ltr">{{ json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </details>
</section>
