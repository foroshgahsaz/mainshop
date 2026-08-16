<x-filament-panels::page>
    <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        صفحه اختصاصی درگاه اعتباری تارا. اگر این جعبه را می‌بینید، کد جدید روی سامانه آمده است.
        کد: <strong dir="ltr">TARA-PAGE-2026-08-16</strong>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" size="lg">
                ذخیره تنظیمات تارا
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
