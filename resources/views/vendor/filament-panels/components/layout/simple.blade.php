@props([
    'heading' => null,
    'subheading' => null,
])

<x-filament-panels::layout.base>
    <link rel="stylesheet" href="{{ asset('fonts/yekan/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('adminpanel/login.css') }}">
    <div class="fi-admin-login-wrap d-flex align-items-center justify-content-center">
        <div class="login-container w-100" style="max-width:960px;margin:0 auto;padding:1rem;">
            <div class="row g-0 shadow-lg rounded-4 overflow-hidden bg-white">
                <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center p-5"
                     style="background:linear-gradient(135deg,#7239ea 0%,#9d5cff 100%);color:#fff;">
                    <div class="text-center">
                        <div class="logo-small mx-auto mb-4" style="width:64px;height:64px;font-size:28px;">چ</div>
                        <h2 class="fw-bold mb-3">{{ filament()->getBrandName() }}</h2>
                        <p class="mb-0 opacity-75">پنل مدیریت فروشگاه چاپینو</p>
                    </div>
                </div>
                <div class="col-lg-6 p-4 p-md-5">
                    <h2 class="login-title mb-1">{{ $heading ?? 'ورود به پنل' }}</h2>
                    @if ($subheading)
                        <p class="text-muted mb-4">{{ $subheading }}</p>
                    @endif
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
