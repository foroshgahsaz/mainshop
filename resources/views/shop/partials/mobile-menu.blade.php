@php
    $megaMenu = $megaMenu ?? ['has_families' => false, 'families' => []];
    $megaFamilies = $megaMenu['families'] ?? [];
    $hasMegaFamilies = !empty($megaMenu['has_families']) && $megaFamilies !== [];
@endphp

<!-- MOBILE MENU -->
<div id="mobileMenu"
     class="shop-mobile-menu"
     onclick="toggleElement('mobileMenu', false)">
  <div data-mobile-panel
       class="shop-mobile-menu__panel"
       onclick="event.stopPropagation()">
    <div class="flex items-center justify-between border-b pb-4">
      <span class="font-black text-brand-green text-lg">منو {{ site_name() }}</span>
      <button type="button" onclick="toggleElement('mobileMenu', false)" aria-label="بستن">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <nav class="main-nav flex flex-col gap-1">
      @foreach(($navigation['mobile'] ?? collect()) as $item)
        @if($item->item_type === \App\Models\MenuItem::TYPE_BRANDS_TRIGGER)
          <button type="button"
                  onclick="toggleAccordion('mobileBrandsMenu')"
                  class="main-nav-link flex justify-between items-center py-3 px-2 text-right w-full">
            {{ $item->label }}
            <svg class="w-3 h-3 transition-transform" data-accordion-icon fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div id="mobileBrandsMenu" class="hidden shop-mobile-accordion-panel shop-mobile-accordion-panel--brands">
            @forelse(($navBrands ?? collect()) as $brand)
              <a href="{{ route('brands.show', $brand) }}" class="py-1 hover:text-brand-green">{{ $brand->name }}</a>
            @empty
              <span class="py-1 text-gray-400">برندی ثبت نشده است.</span>
            @endforelse
          </div>
        @elseif($item->item_type !== \App\Models\MenuItem::TYPE_MEGA_TRIGGER && $item->item_type !== \App\Models\MenuItem::TYPE_MEGA_PROMO)
          @include('shop.partials.menu-mobile-link', ['item' => $item])
        @endif
      @endforeach

      @if($hasMegaFamilies)
        <button type="button"
                onclick="toggleAccordion('mobileMegaMenu'); resetMobileMegaSplit();"
                class="main-nav-link flex justify-between items-center py-3 px-2 text-right w-full">
          محصولات
          <svg class="w-3 h-3 transition-transform" data-accordion-icon fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        @include('shop.partials.mobile-mega-split', ['megaFamilies' => $megaFamilies])
      @else
        <button type="button"
                onclick="toggleAccordion('mobileMegaMenu')"
                class="main-nav-link flex justify-between items-center py-3 px-2 text-right w-full">
          محصولات
          <svg class="w-3 h-3 transition-transform" data-accordion-icon fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div id="mobileMegaMenu" class="hidden shop-mobile-accordion-panel shop-mobile-accordion-panel--mega">
          <a href="{{ route('products.index') }}" class="py-1 hover:text-brand-green font-bold">همه محصولات</a>
          @foreach(($navCategories ?? collect()) as $cat)
            <a href="{{ route('categories.show', $cat) }}" class="py-1 hover:text-brand-green">{{ $cat->name }}</a>
            @foreach($cat->children as $child)
              <a href="{{ route('categories.show', $child) }}" class="py-1 pr-3 hover:text-brand-green">— {{ $child->name }}</a>
            @endforeach
          @endforeach
        </div>
      @endif

      @auth
        <a href="{{ route('account.orders') }}" class="main-nav-link py-3 px-2 hover:text-brand-green">سفارش‌های من</a>
        <a href="{{ route('account.dashboard') }}" class="main-nav-link py-3 px-2 hover:text-brand-green">حساب کاربری</a>
      @endauth
    </nav>

    @guest
      <button type="button"
              data-open-login
              onclick="toggleElement('mobileMenu', false)"
              class="mt-auto bg-brand-gold text-white py-3 rounded-xl font-bold text-sm">
        ورود / ثبت نام
      </button>
    @endguest
  </div>
</div>
