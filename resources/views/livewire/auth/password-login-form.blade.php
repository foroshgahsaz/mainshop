<form wire:submit="loginWithPassword" class="space-y-4">
    <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">نام کاربری</label>
        <input type="text" wire:model="username" dir="ltr"
               placeholder="09123456789 یا email@example.com"
               autofocus
               autocomplete="username"
               class="w-full border-2 border-gray-200 rounded-xl py-3 px-4 text-sm outline-none focus:border-brand-green">
        @error('username') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
    </div>
    <div>
        <label class="block text-sm font-bold text-gray-700 mb-2">رمز عبور</label>
        <input type="password" wire:model="password"
               placeholder="رمز عبور"
               autocomplete="current-password"
               class="w-full border-2 border-gray-200 rounded-xl py-3 px-4 text-sm outline-none focus:border-brand-green">
        @error('password') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
    </div>
    <button type="submit" wire:loading.attr="disabled"
            class="w-full bg-brand-gold hover:bg-amber-600 text-white font-bold py-3 rounded-xl transition">
        <span wire:loading.remove wire:target="loginWithPassword">ورود</span>
        <span wire:loading wire:target="loginWithPassword">در حال ورود...</span>
    </button>
</form>

<p class="mt-6 text-center text-xs text-gray-400 leading-6">
    با ورود، شرایط استفاده از فروشگاه چاپینو را می‌پذیرید.
</p>
