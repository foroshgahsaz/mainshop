<!-- MOBILE MENU -->
<div id="mobileMenu"
     class="shop-mobile-menu"
     onclick="toggleElement('mobileMenu', false)">
  <div data-mobile-panel
       class="shop-mobile-menu__panel"
       onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-4">
      <span class="font-black text-brand-green text-lg">منو چاپینو</span>
      <button type="button" onclick="toggleElement('mobileMenu', false)" aria-label="بستن">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <nav class="main-nav flex flex-col gap-1">
      @foreach(($navigation['mobile'] ?? collect()) as $item)
        @if($item->item_type !== \App\Models\MenuItem::TYPE_MEGA_TRIGGER && $item->item_type !== \App\Models\MenuItem::TYPE_MEGA_PROMO)
          @include('shop.partials.menu-mobile-link', ['item' => $item])
        @endif
      @endforeach

      <button type="button"
              onclick="toggleAccordion('mobileMegaMenu')"
              class="main-nav-link flex justify-between items-center py-3 px-2 text-right w-full">
        محصولات
        <svg id="accordionIcon" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="m6 9 6 6 6-6"/>
        </svg>
      </button>
      <div id="mobileMegaMenu" class="hidden flex-col gap-2 pr-4 text-xs text-gray-500 border-r-2 border-emerald-100 mr-2">
        <a href="{{ route('products.index') }}" class="py-1 hover:text-brand-green font-bold">همه محصولات</a>
        @foreach(($navCategories ?? collect()) as $cat)
          <a href="{{ route('categories.show', $cat) }}" class="py-1 hover:text-brand-green">{{ $cat->name }}</a>
          @foreach($cat->children as $child)
            <a href="{{ route('categories.show', $child) }}" class="py-1 pr-3 hover:text-brand-green">— {{ $child->name }}</a>
          @endforeach
        @endforeach
      </div>

      @auth
        <a href="{{ route('account.orders') }}" class="main-nav-link py-3 px-2 hover:text-brand-green">سفارش‌های من</a>
        <a href="{{ route('account.dashboard') }}" class="main-nav-link py-3 px-2 hover:text-brand-green">حساب کاربری</a>
      @endauth
    </nav>

    @guest
      <button type="button"
              onclick="toggleElement('mobileMenu', false); toggleElement('loginModal', true)"
              class="mt-auto bg-brand-gold text-white py-3 rounded-xl font-bold text-sm">
        ورود / ثبت نام
      </button>
    @endguest
  </div>
</div>
