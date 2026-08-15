<div class="container mx-auto px-4 py-8 max-w-md">
    <h1 class="text-2xl font-bold mb-6">ثبت‌نام</h1>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label class="block mb-1">نام</label>
            <input type="text" wire:model="name" class="w-full border rounded px-3 py-2">
            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block mb-1">شماره موبایل</label>
            <input type="text" wire:model="phone" class="w-full border rounded px-3 py-2">
            @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block mb-1">ایمیل (اختیاری)</label>
            <input type="email" wire:model="email" class="w-full border rounded px-3 py-2">
            @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block mb-1">رمز عبور</label>
            <input type="password" wire:model="password" class="w-full border rounded px-3 py-2">
            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block mb-1">تکرار رمز عبور</label>
            <input type="password" wire:model="password_confirmation" class="w-full border rounded px-3 py-2">
        </div>
        <button type="submit" class="w-full bg-[#6c7fd8] text-white py-2 rounded-lg">ثبت‌نام</button>
    </form>

    <p class="mt-4 text-center text-sm">
        قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ route('login') }}" class="text-[#6c7fd8]">ورود</a>
    </p>
</div>
