@php
    $isDesktop = $variant === 'desktop';
    $btnClass = $isDesktop ? 'header-login-btn' : 'header-icon-btn';
@endphp

<div class="relative header-user-dropdown" data-header-user-menu>
    @auth
        <button type="button"
                class="{{ $btnClass }} header-user-dropdown-toggle"
                aria-expanded="false"
                aria-haspopup="true"
                aria-label="حساب کاربری">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            @if ($isDesktop)
                <span class="max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                <svg class="header-user-chevron w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            @endif
        </button>

        <div class="header-user-menu" data-header-user-menu-panel role="menu">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="font-bold text-sm text-navy truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-400 truncate" dir="ltr">{{ auth()->user()->phone }}</p>
            </div>
            <a href="{{ route('account.dashboard') }}" class="header-user-menu-item">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                داشبورد
            </a>
            <a href="{{ route('account.orders') }}" class="header-user-menu-item">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                سفارش‌ها
            </a>
            <a href="{{ route('account.payments') }}" class="header-user-menu-item">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                پرداخت‌ها
            </a>
            <a href="{{ route('account.profile') }}" class="header-user-menu-item">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/></svg>
                مشخصات
            </a>
            <a href="{{ route('account.addresses') }}" class="header-user-menu-item">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                آدرس‌ها
            </a>
            <a href="{{ route('account.wishlist') }}" class="header-user-menu-item">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                علاقه‌مندی‌ها
            </a>
            <button type="button" wire:click="logout" class="header-user-menu-item text-red-600 w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                خروج
            </button>
        </div>
    @else
        <button type="button"
                onclick="toggleElement('loginModal', true)"
                class="{{ $btnClass }}"
                aria-label="ورود / ثبت نام">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
            @if ($isDesktop)
                <span>ورود / ثبت نام</span>
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            @endif
        </button>
    @endauth
</div>
