  <!-- HEADER -->
    <header class="shop-header w-full bg-white border-b border-gray-200 sticky z-40">
    <div class="max-w-site mx-auto px-4 md:px-6 pb-3 pt-4 md:pt-5">
      <div class="flex items-center justify-between gap-3 mb-3 md:mb-4">
        <div class="flex items-center gap-2 md:gap-3">
                <button onclick="toggleElement('mobileMenu', true)"
                  class="md:hidden p-2 text-gray-600 hover:text-navy"
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
          <a href="{{ route('home') }}"
             class="flex items-center gap-2">
            <span
                  class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-green to-accent-teal flex items-center justify-center text-white text-sm font-black">چ</span>
            <span class="text-xl md:text-2xl font-black text-navy">چاپینو</span>
          </a>
                </div>

                <div class="hidden md:block flex-1 max-w-2xl mx-8">
                    <div class="relative">
                    <form action="{{ route('products.index') }}" method="GET" class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="جستجو در چاپینو..."
                   class="w-full bg-white border-2 border-gray-200 rounded-xl py-3 px-5 pr-12 outline-none focus:border-brand-green text-sm">
            <svg class="w-5 h-5 absolute right-4 top-3.5 text-gray-400"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24">
              <circle cx="11"
                      cy="11"
                      r="7" />
              <path d="m20 20-3.5-3.5" />
            </svg>
                    </form>
                    </div>
                </div>

        <div class="flex items-center gap-1 md:gap-0">
                    <button onclick="toggleSearchModal(true)"
                  class="md:hidden p-2 text-gray-600 hover:text-navy"
                  aria-label="جستجو">
            <svg class="w-5 h-5"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24">
              <circle cx="11"
                      cy="11"
                      r="7" />
              <path d="m20 20-3.5-3.5" />
            </svg>
                    </button>

          <!-- Desktop: phone | login | cart -->
          <div class="hidden md:flex items-center">
            <a href="tel:*"
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

          <!-- Mobile: phone, cart -->
          <a href="tel:*"
             class="md:hidden header-icon-btn"
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
          <div class="md:hidden">@livewire('cart.cart-counter', key('cart-mobile'))</div>
          <div class="md:hidden">@livewire('layout.header-auth', ['variant' => 'mobile'], key('header-auth-mobile'))</div>
                </div>
            </div>

      <nav class="main-nav hidden md:flex items-center gap-5 border-t pt-3 border-gray-200">
        @foreach(($navigation['desktop'] ?? collect()) as $item)
          @include('shop.partials.menu-desktop-link', ['item' => $item])
        @endforeach
      </nav>
        </div>
    </header>