<x-account-layout title="مدیریت آدرس‌ها" active="addresses">
    @if (session('success'))
        <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="shop-card p-5 mb-6 space-y-4 max-w-2xl">
        <h2 class="font-bold text-navy">{{ $editingId ? 'ویرایش آدرس' : 'آدرس جدید' }}</h2>
        <input type="text" wire:model="receiver_name" placeholder="نام گیرنده" class="shop-input">
        <input type="text" wire:model="receiver_phone" placeholder="موبایل گیرنده" class="shop-input" dir="ltr">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <input type="text" wire:model="province" placeholder="استان" class="shop-input">
            <input type="text" wire:model="city" placeholder="شهر" class="shop-input">
        </div>
        <textarea wire:model="address" placeholder="آدرس کامل" class="shop-input" rows="2"></textarea>
        <input type="text" wire:model="postal_code" placeholder="کد پستی" class="shop-input" dir="ltr">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_default"> آدرس پیش‌فرض</label>
        <div class="flex gap-2">
            <button type="submit" class="shop-btn-primary">ذخیره</button>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="shop-btn-outline">انصراف</button>
            @endif
        </div>
    </form>

    <div class="space-y-3 max-w-2xl">
        @foreach ($addresses as $address)
            <div class="shop-card p-4 flex justify-between gap-4" wire:key="addr-{{ $address->id }}">
                <div>
                    <p class="font-bold text-sm text-navy">{{ $address->receiver_name }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $address->city }} — {{ $address->address }}</p>
                    @if ($address->is_default)
                        <span class="inline-block mt-2 text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">پیش‌فرض</span>
                    @endif
                </div>
                <div class="flex gap-2 shrink-0">
                    <button wire:click="edit({{ $address->id }})" class="text-brand-green text-sm font-bold">ویرایش</button>
                    <button wire:click="delete({{ $address->id }})" wire:confirm="حذف شود؟" class="text-red-600 text-sm">حذف</button>
                </div>
            </div>
        @endforeach
    </div>
</x-account-layout>
