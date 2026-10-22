@php
    use App\Filament\Resources\OrderResource;
    use App\Services\Checkout\CouponAnalyticsService;
    use App\Support\ShopLabels;

    /** @var \App\Models\Coupon $record */
    $usages = app(CouponAnalyticsService::class)->recentUsages($record);
@endphp

<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <x-slot name="heading">تعداد استفاده</x-slot>
            <p class="text-2xl font-bold">{{ number_format($summary['usage_count']) }}
                @if ($summary['usage_limit'])
                    <span class="text-sm font-normal text-gray-500">/ {{ number_format($summary['usage_limit']) }}</span>
                @endif
            </p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">کاربران یکتا</x-slot>
            <p class="text-2xl font-bold">{{ number_format($summary['unique_users']) }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">جمع تخفیف</x-slot>
            <p class="text-2xl font-bold">{{ ShopLabels::formatMoney($summary['total_discount']) }}</p>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">جمع فروش (پس از تخفیف)</x-slot>
            <p class="text-2xl font-bold">{{ ShopLabels::formatMoney($summary['total_revenue']) }}</p>
            <p class="text-xs text-gray-500 mt-1">میانگین سفارش: {{ ShopLabels::formatMoney($summary['average_order_amount']) }}</p>
        </x-filament::section>
    </div>

    <x-filament::section class="mt-6">
        <x-slot name="heading">جزئیات کد</x-slot>
        <dl class="grid gap-3 text-sm md:grid-cols-2">
            <div><dt class="text-gray-500">نوع</dt><dd>{{ $record->type === 'percent' ? 'درصدی' : 'مبلغ ثابت' }} — {{ $record->value }}</dd></div>
            <div><dt class="text-gray-500">حداقل سفارش</dt><dd>{{ $record->minimum_order_amount ? ShopLabels::formatMoney($record->minimum_order_amount) : '—' }}</dd></div>
            <div><dt class="text-gray-500">فعال</dt><dd>{{ $record->is_active ? 'بله' : 'خیر' }}</dd></div>
            <div><dt class="text-gray-500">ترکیب با کدهای دیگر</dt><dd>{{ $record->stackable_with_other_coupons ? 'مجاز' : 'غیرمجاز' }}</dd></div>
        </dl>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">سوابق استفاده</x-slot>
        @if ($usages->isEmpty())
            <p class="text-sm text-gray-500">هنوز استفاده‌ای ثبت نشده است.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="py-2 pe-3">زمان</th>
                            <th class="py-2 pe-3">کاربر</th>
                            <th class="py-2 pe-3">موبایل</th>
                            <th class="py-2 pe-3">شماره سفارش</th>
                            <th class="py-2 pe-3">تخفیف</th>
                            <th class="py-2 pe-3">مبلغ نهایی</th>
                            <th class="py-2">وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($usages as $usage)
                            @php $order = $usage->order; @endphp
                            <tr class="border-b border-gray-100">
                                <td class="py-2 pe-3 whitespace-nowrap">{{ optional($usage->used_at)->shopJalaliDateTime() }}</td>
                                <td class="py-2 pe-3">{{ $usage->user?->name ?? '—' }}</td>
                                <td class="py-2 pe-3" dir="ltr">{{ $usage->user?->phone ?? '—' }}</td>
                                <td class="py-2 pe-3">
                                    @if ($order)
                                        <a href="{{ OrderResource::getUrl('view', ['record' => $order]) }}" class="text-primary-600 hover:underline">{{ $order->tracking_code }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 pe-3">{{ $order ? ShopLabels::formatMoney($order->discount_amount) : '—' }}</td>
                                <td class="py-2 pe-3">{{ $order ? ShopLabels::formatMoney($order->final_amount) : '—' }}</td>
                                <td class="py-2">{{ $order ? ShopLabels::orderStatus($order->status) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
