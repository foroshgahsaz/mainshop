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
            <svg class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div id="mobileBrandsMenu" class="hidden flex-col gap-2 pr-4 text-xs text-gray-500 border-r-2 border-emerald-100 mr-2 mb-2">
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
                onclick="toggleAccordion('mobileMegaMenu'); resetMobileMegaDrill();"
                class="main-nav-link flex justify-between items-center py-3 px-2 text-right w-full">
          محصولات
          <svg id="accordionIcon" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div id="mobileMegaMenu"
             class="hidden shop-mobile-mega-drill pr-2 text-xs text-gray-600 border-r-2 border-emerald-100 mr-2"
             data-mobile-mega-drill>
          <div class="shop-mobile-mega-drill__toolbar hidden mb-2" data-mega-drill-toolbar>
            <button type="button"
                    class="shop-mobile-mega-drill__back flex items-center gap-1 py-2 text-brand-green font-bold"
                    data-mega-drill-back>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 6l6 6-6 6"/>
              </svg>
              بازگشت
            </button>
            <p class="text-[11px] text-gray-400 mt-1" data-mega-drill-title></p>
          </div>

          <div class="shop-mobile-mega-drill__level flex flex-col gap-1" data-mega-drill-level="families">
            @foreach($megaFamilies as $family)
              <button type="button"
                      class="shop-mobile-mega-drill__row py-2 px-2 text-right w-full flex justify-between items-center hover:text-brand-green"
                      data-mega-drill-open="family-{{ $family['id'] }}">
                <span>{{ $family['name'] }}</span>
                <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="m9 6 6 6-6 6"/>
                </svg>
              </button>
            @endforeach
          </div>

          @foreach($megaFamilies as $family)
            <div class="shop-mobile-mega-drill__level hidden flex-col gap-1"
                 data-mega-drill-level="family-{{ $family['id'] }}"
                 hidden>
              <a href="{{ $family['all_products_url'] }}"
                 class="py-2 px-2 text-brand-green font-bold hover:underline"
                 onclick="toggleElement('mobileMenu', false)">همه محصولات {{ $family['name'] }}</a>
              @foreach($family['columns'] as $colIndex => $column)
                @if(!empty($column['items']))
                  <button type="button"
                          class="shop-mobile-mega-drill__row py-2 px-2 text-right w-full flex justify-between items-center hover:text-brand-green"
                          data-mega-drill-open="column-{{ $family['id'] }}-{{ $colIndex }}">
                    <span>{{ $column['title'] }}</span>
                    <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="m9 6 6 6-6 6"/>
                    </svg>
                  </button>
                @else
                  <a href="{{ $column['url'] }}"
                     class="py-2 px-2 hover:text-brand-green"
                     onclick="toggleElement('mobileMenu', false)">{{ $column['title'] }}</a>
                @endif
              @endforeach
            </div>

            @foreach($family['columns'] as $colIndex => $column)
              @if(!empty($column['items']))
                <div class="shop-mobile-mega-drill__level hidden flex-col gap-1"
                     data-mega-drill-level="column-{{ $family['id'] }}-{{ $colIndex }}"
                     hidden>
                  <a href="{{ $column['url'] }}"
                     class="py-2 px-2 font-bold text-gray-800 hover:text-brand-green"
                     onclick="toggleElement('mobileMenu', false)">{{ $column['title'] }}</a>
                  @foreach($column['items'] as $item)
                    <a href="{{ $item['url'] }}"
                       class="py-1.5 px-2 pr-4 hover:text-brand-green"
                       onclick="toggleElement('mobileMenu', false)">{{ $item['label'] }}</a>
                  @endforeach
                </div>
              @endif
            @endforeach
          @endforeach
        </div>
      @else
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
