@php
    $metrics = $metrics ?? [];
    $gateways = $gateways ?? [];
    $templates = $templates ?? [];
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="خلاصه و پیشنهادها" icon="heroicon-o-light-bulb">
        <div class="rep-dashboard-insights">
            <ul class="rep-dashboard-insights__tips">
                <li>
                    <strong>{{ number_format((int) ($metrics['customers_count'] ?? 0)) }}</strong>
                    مشتری ثبت‌شده توسط شما — از منوی مشتریان می‌توانید شخص جدید اضافه کنید.
                </li>
                @if (($metrics['proforma_unpaid_count'] ?? 0) > 0)
                    <li class="rep-dashboard-insights__warn">
                        <strong>{{ (int) $metrics['proforma_unpaid_count'] }}</strong>
                        پیش‌فاکتور هنوز تسویه نشده؛ از لیست پیش‌فاکتورها پرداخت را انجام دهید.
                    </li>
                @endif
                @if (($metrics['expiring_reservations_count'] ?? 0) > 0)
                    <li class="rep-dashboard-insights__warn">
                        <strong>{{ (int) $metrics['expiring_reservations_count'] }}</strong>
                        رزرو موجودی تا ۴۸ ساعت دیگر منقضی می‌شود — در صورت نیاز با ادمین برای تمدید هماهنگ کنید.
                    </li>
                @endif
                <li>
                    برای ثبت سریع سفارش از دکمه <strong>سفارش جدید</strong> بالای صفحه یا منوی پیش‌سفارش استفاده کنید.
                </li>
            </ul>

            @if (count($templates) > 0)
                <h4 class="rep-dashboard-insights__subtitle">فروش ماه جاری به تفکیک نوع طرح (قالب)</h4>
                <ul class="rep-dashboard-insights__breakdown">
                    @foreach ($templates as $row)
                        <li>
                            <span>{{ $row['label'] }}</span>
                            <strong>{{ $row['total_formatted'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($gateways->isNotEmpty())
                <h4 class="rep-dashboard-insights__subtitle">جزئیات درگاه‌ها (ماه جاری)</h4>
                <ul class="rep-dashboard-insights__breakdown">
                    @foreach ($gateways as $row)
                        <li>
                            <span>{{ $row['label'] }}</span>
                            <strong>{{ \App\Support\ShopFormatter::money($row['total']) }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
