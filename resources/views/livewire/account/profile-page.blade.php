<x-account-layout title="مشخصات من" active="profile">
    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="shop-card p-6 space-y-5 max-w-lg">
        <div>
            <label class="shop-label">نام و نام خانوادگی</label>
            <input type="text" wire:model="name" class="shop-input">
            @error('name') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="shop-label">شماره موبایل</label>
            <input type="text" value="{{ auth()->user()->phone }}" dir="ltr" disabled class="shop-input bg-gray-50 text-gray-500">
        </div>
        <div>
            <label class="shop-label">ایمیل (اختیاری)</label>
            <input type="email" wire:model="email" dir="ltr" class="shop-input">
            @error('email') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="shop-btn-primary">
            <span wire:loading.remove wire:target="save">ذخیره تغییرات</span>
            <span wire:loading wire:target="save">در حال ذخیره...</span>
        </button>
    </form>
</x-account-layout>
