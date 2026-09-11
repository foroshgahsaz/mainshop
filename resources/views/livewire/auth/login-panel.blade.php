<div class="flex border-b bg-gray-50 text-sm">
    <button type="button"
            wire:click="$set('activeTab', 'otp')"
            class="flex-1 py-4 border-b-2 transition-colors {{ $activeTab === 'otp' ? 'border-brand-green text-brand-green font-bold' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        ورود با موبایل
    </button>
    <button type="button"
            wire:click="$set('activeTab', 'password')"
            class="flex-1 py-4 border-b-2 transition-colors {{ $activeTab === 'password' ? 'border-brand-green text-brand-green font-bold' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        ورود با نام کاربری
    </button>
</div>

<div class="p-6">
    @if ($activeTab === 'otp')
        <div class="text-center mb-6">
            <h2 class="text-lg font-black text-navy">ورود / ثبت‌نام</h2>
            <p class="text-sm text-gray-500 mt-1">برای خرید، کد یکبار مصرف دریافت کنید</p>
        </div>

        @include('livewire.auth.login-form')
    @else
        <div class="text-center mb-6">
            <h2 class="text-lg font-black text-navy">ورود با نام کاربری</h2>
            <p class="text-sm text-gray-500 mt-1">با شماره موبایل یا ایمیل و رمز عبور وارد شوید</p>
        </div>

        @include('livewire.auth.password-login-form')
    @endif
</div>
