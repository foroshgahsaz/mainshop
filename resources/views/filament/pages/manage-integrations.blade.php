<x-filament-panels::page>
    <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900">
        این صفحه سه تب دارد: زرین‌پال، <strong>تارا</strong> و کاوه‌نگار.
        اگر تب تارا را نمی‌بینید، مستقیم بروید به
        <a href="{{ \App\Filament\Pages\ManageTara::getUrl() }}" class="font-bold underline">تنظیمات تارا</a>.
        کد: <strong dir="ltr">TARA-SYNC-2026-08-16</strong>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" size="lg">
                ذخیره تنظیمات
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
