@php
    use App\Support\ShopLabels;
@endphp

<x-account-layout title="سفارش‌های من" active="orders">
    <div class="shop-card divide-y divide-gray-100">
        @forelse ($orders as $order)
            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3" wire:key="order-{{ $order->id }}">
                <div>
                    <p class="font-bold text-sm text-navy">کد پیگیری: {{ $order->tracking_code }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $order->created_at->format('Y/m/d H:i') }}</p>
                    <p class="text-sm mt-2">
                        <span class="font-bold text-brand-green">{{ number_format($order->final_amount) }} تومان</span>
                        <span class="text-gray-400 mx-2">|</span>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ ShopLabels::orderStatus($order->status) }}</span>
                    </p>
                </div>
                <a href="{{ route('account.orders.show', $order) }}" class="shop-btn-outline text-sm">جزئیات سفارش</a>
            </div>
        @empty
            <div class="p-8 text-center text-gray-500 text-sm">
                سفارشی ثبت نشده است.
                <a href="{{ route('products.index') }}" class="block mt-2 text-brand-green font-bold">شروع خرید</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
</x-account-layout>
