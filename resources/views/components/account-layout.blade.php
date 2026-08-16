@props(['title', 'active' => ''])

<div class="shop-page-wrap">
    <div class="max-w-site mx-auto px-4 account-page">
        <nav class="flex items-center gap-2 text-xs text-gray-400 account-page__crumbs">
            <a href="{{ route('home') }}" class="hover:text-brand-green">خانه</a>
            <span>/</span>
            <a href="{{ route('account.dashboard') }}" class="hover:text-brand-green">حساب کاربری</a>
            @if ($title)
                <span>/</span>
                <span class="text-gray-600">{{ $title }}</span>
            @endif
        </nav>

        <div class="account-shell">
            <aside class="account-sidebar">
                <div class="shop-card account-sidebar__card">
                    <div class="account-sidebar__user">
                        <span class="account-sidebar__avatar">
                            {{ mb_substr(auth()->user()->name ?: 'ک', 0, 1) }}
                        </span>
                        <div class="min-w-0">
                            <p class="account-sidebar__name">{{ auth()->user()->name }}</p>
                            <p class="account-sidebar__phone" dir="ltr">{{ auth()->user()->phone }}</p>
                        </div>
                    </div>
                    <nav class="account-sidebar__nav" aria-label="منوی حساب کاربری">
                        @foreach ([
                            'dashboard' => ['label' => 'داشبورد', 'route' => 'account.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            'orders' => ['label' => 'سفارش‌ها', 'route' => 'account.orders', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                            'payments' => ['label' => 'پرداخت‌ها', 'route' => 'account.payments', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                            'profile' => ['label' => 'مشخصات', 'route' => 'account.profile', 'icon' => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z'],
                            'addresses' => ['label' => 'آدرس‌ها', 'route' => 'account.addresses', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
                            'wishlist' => ['label' => 'علاقه‌مندی‌ها', 'route' => 'account.wishlist', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
                        ] as $key => $item)
                            <a href="{{ route($item['route']) }}"
                               class="account-nav-link {{ $active === $key ? 'account-nav-link-active' : '' }}">
                                <svg class="account-nav-link__icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                </svg>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div class="account-main">
                @if ($title)
                    <h1 class="account-main__title">{{ $title }}</h1>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
