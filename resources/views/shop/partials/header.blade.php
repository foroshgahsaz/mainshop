  <!-- HEADER -->
    <header class="shop-header w-full bg-white border-b border-gray-200 sticky z-40">
    <div class="max-w-site mx-auto px-3 md:px-6 pb-2 pt-2 md:pb-3 md:pt-5 shop-header__inner">
      @php
          $sitePhone = app(\App\Services\Settings\SettingsService::class)->site()['phone'] ?? '';
          $siteTel = preg_replace('/\D+/', '', (string) $sitePhone);
      @endphp

      {{-- موبایل: تماس چپ | (لوگو + منو) راست — ترتیب فیزیکی LTR برای پایداری در RTL --}}
      <div class="shop-header__mobile flex md:hidden items-center w-full mb-0">
        <span class="header-phone-wrap shop-header__mobile-phone shrink-0">
          <a href="{{ $siteTel !== '' ? 'tel:'.$siteTel : '#' }}"
             class="header-phone-btn"
             aria-label="تماس">
            <span class="header-phone-btn__waves" aria-hidden="true">
              <span></span>
              <span></span>
            </span>
            <svg class="header-phone-btn__icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
              <path
                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
          </a>
        </span>
        <div class="shop-header__mobile-spacer flex-1 min-w-2" aria-hidden="true"></div>
        <div class="shop-header__mobile-end flex items-center gap-2 shrink-0">
          <a href="{{ route('home') }}"
             class="shop-header__mobile-logo shrink-0 min-w-0"
             aria-label="{{ site_name() }}">
            @if ($logoUrl = site_logo_url())
              <img src="{{ $logoUrl }}"
                   alt="{{ site_name() }}"
                   class="header-site-logo h-9 w-auto max-w-[9.5rem] object-contain object-center"
                   width="192"
                   height="48"
                   decoding="async"
                   fetchpriority="high">
            @else
              <span class="text-lg font-black text-navy truncate">{{ site_name() }}</span>
            @endif
          </a>
          <button type="button"
                  onclick="toggleElement('mobileMenu', true)"
                  class="shop-header__mobile-menu shrink-0"
                  aria-label="منو">
            <svg class="w-6 h-6"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    d="M4 7h16M4 12h16M4 17h16" />
            </svg>
          </button>
        </div>
      </div>

      <div class="hidden md:flex items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-3">
          <a href="{{ route('home') }}"
             class="flex items-center shrink-0"
             aria-label="{{ site_name() }}">
            @if ($logoUrl = site_logo_url())
              <img src="{{ $logoUrl }}"
                   alt="{{ site_name() }}"
                   class="header-site-logo h-11 w-auto max-w-[12rem] object-contain object-right"
                   width="192"
                   height="48"
                   decoding="async"
                   fetchpriority="high">
            @else
              <span class="text-2xl font-black text-navy">{{ site_name() }}</span>
            @endif
          </a>
        </div>

        <div class="flex-1 max-w-2xl mx-8">
          <div class="header-search-suggest relative"
               id="headerSearchSuggest"
               data-suggest-url="{{ route('shop.search.suggest') }}"
               data-min-chars="3">
            <form action="{{ route('products.index') }}" method="GET" class="relative" id="headerSearchForm">
              <input type="search"
                     name="search"
                     id="headerSearchInput"
                     value="{{ request('search') }}"
                     placeholder="جستجو در {{ site_name() }}..."
                     autocomplete="off"
                     aria-autocomplete="list"
                     aria-controls="headerSearchDropdown"
                     class="w-full bg-white border-2 border-gray-200 rounded-xl py-3 px-5 pr-12 outline-none focus:border-brand-green text-sm">
              <svg class="w-5 h-5 absolute right-4 top-3.5 text-gray-400 pointer-events-none"
                   fill="none"
                   stroke="currentColor"
                   stroke-width="2"
                   viewBox="0 0 24 24"
                   aria-hidden="true">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </form>
            <div id="headerSearchDropdown"
                 class="header-search-suggest__dropdown hidden"
                 aria-live="polite"
                 role="listbox"></div>
          </div>
        </div>

        <div class="flex items-center">
          <a href="{{ $siteTel !== '' ? 'tel:'.$siteTel : '#' }}"
             class="header-icon-btn"
             aria-label="تماس">
            <svg class="w-5 h-5"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.75"
                 viewBox="0 0 24 24">
              <path
                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
          </a>
          <span class="header-divider mx-3"></span>
          @livewire('layout.header-auth', ['variant' => 'desktop'])
          <span class="header-divider mx-3"></span>
          @livewire('cart.cart-counter')
        </div>
      </div>

      <nav class="main-nav hidden md:flex items-center gap-5 border-t pt-3 border-gray-200">
        @foreach(($navigation['desktop'] ?? collect()) as $item)
          @include('shop.partials.menu-desktop-link', ['item' => $item])
        @endforeach
      </nav>
        </div>
    </header>
