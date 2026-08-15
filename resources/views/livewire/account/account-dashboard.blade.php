<x-account-layout title="داشبورد" active="dashboard">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="shop-stat-card">
            <p class="shop-stat-label">کل سفارش‌ها</p>
            <p class="shop-stat-value">{{ $ordersCount }}</p>
        </div>
        <div class="shop-stat-card">
            <p class="shop-stat-label">سفارش‌های جاری</p>
            <p class="shop-stat-value text-brand-gold">{{ $pendingOrders }}</p>
        </div>
        <div class="shop-stat-card">
            <p class="shop-stat-label">پرداخت‌های موفق</p>
            <p class="shop-stat-value text-brand-green">{{ $paymentsCount }}</p>
        </div>
    </div>

    <div class="shop-card">
        <div class="shop-card-header">
            <h2 class="font-bold text-navy">آخرین سفارش‌ها</h2>
            <a href="{{ route('account.orders') }}" class="text-sm text-brand-green font-bold">مشاهده همه</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($recentOrders as $order)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="font-bold text-sm text-navy">کد پیگیری: {{ $order->tracking_code }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $order->created_at->format('Y/m/d H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-bold text-brand-green">{{ number_format($order->final_amount) }} تومان</span>
                        <a href="{{ route('account.orders.show', $order) }}" class="text-xs text-brand-green font-bold">جزئیات</a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500 text-sm">
                    هنوز سفارشی ثبت نکرده‌اید.
                    <a href="{{ route('products.index') }}" class="block mt-2 text-brand-green font-bold">شروع خرید</a>
                </div>
            @endforelse
        </div>
    </div>
</x-account-layout>
