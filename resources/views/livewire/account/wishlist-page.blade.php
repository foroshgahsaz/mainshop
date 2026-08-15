<x-account-layout title="علاقه‌مندی‌ها" active="wishlist">
    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($items as $item)
            <div class="shop-card p-4" wire:key="wish-{{ $item->id }}">
                <a href="{{ route('products.show', $item->product) }}" class="font-bold text-sm text-navy hover:text-brand-green line-clamp-2">
                    {{ $item->product->name }}
                </a>
                <p class="text-brand-green font-black mt-2 text-sm">{{ number_format($item->product->effective_price) }} تومان</p>
                <button wire:click="remove({{ $item->product_id }})" class="text-red-500 text-xs mt-3 font-medium">حذف از لیست</button>
            </div>
        @empty
            <div class="shop-card p-8 text-center text-gray-500 text-sm col-span-full">
                لیست علاقه‌مندی‌ها خالی است.
                <a href="{{ route('products.index') }}" class="block mt-2 text-brand-green font-bold">مشاهده محصولات</a>
            </div>
        @endforelse
    </div>
</x-account-layout>
