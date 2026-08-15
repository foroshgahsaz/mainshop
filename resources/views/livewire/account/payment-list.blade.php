@php
    $statusLabels = [
        'pending' => ['label' => 'در انتظار', 'class' => 'bg-amber-100 text-amber-700'],
        'success' => ['label' => 'موفق', 'class' => 'bg-emerald-100 text-emerald-700'],
        'failed' => ['label' => 'ناموفق', 'class' => 'bg-red-100 text-red-700'],
        'canceled' => ['label' => 'لغو شده', 'class' => 'bg-gray-100 text-gray-600'],
        'refunded' => ['label' => 'مسترد شده', 'class' => 'bg-blue-100 text-blue-700'],
    ];
@endphp

<x-account-layout title="پرداخت‌ها" active="payments">
    <div class="shop-card divide-y divide-gray-100">
        @forelse ($payments as $payment)
            @php $status = $statusLabels[$payment->status] ?? ['label' => $payment->status, 'class' => 'bg-gray-100 text-gray-600']; @endphp
            <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3" wire:key="payment-{{ $payment->id }}">
                <div>
                    <p class="font-bold text-sm text-navy">{{ number_format($payment->amount) }} تومان</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $payment->created_at->format('Y/m/d H:i') }}
                        @if ($payment->order)
                            — سفارش {{ $payment->order->tracking_code }}
                        @endif
                    </p>
                    @if ($payment->tracking_code)
                        <p class="text-xs text-gray-500 mt-1" dir="ltr">کد پیگیری: {{ $payment->tracking_code }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold px-3 py-1 rounded-full {{ $status['class'] }}">{{ $status['label'] }}</span>
                    @if ($payment->order)
                        <a href="{{ route('account.orders.show', $payment->order) }}" class="text-xs text-brand-green font-bold">سفارش</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-gray-500 text-sm">پرداختی ثبت نشده است.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $payments->links() }}</div>
</x-account-layout>
