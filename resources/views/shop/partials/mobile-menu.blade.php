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
                onclick="toggleAccordion('mobileMegaMenu'); resetMobileMegaDrill();"
                class="main-nav-link flex justify-between items-center py-3 px-2 text-right w-full">
          محصولات
          <svg class="w-3 h-3 transition-transform" data-accordion-icon fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div id="mobileMegaMenu"
             class="hidden shop-mobile-mega-drill"
             data-mobile-mega-drill>
          <div class="shop-mobile-mega-drill__toolbar hidden" data-mega-drill-toolbar>
            <button type="button"
                    class="shop-mobile-mega-drill__back"
                    data-mega-drill-back>
              <svg class="shop-mobile-mega-drill__back-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 6l6 6-6 6"/>
              </svg>
              بازگشت
            </button>
            <p class="shop-mobile-mega-drill__title" data-mega-drill-title></p>
          </div>

          <div class="shop-mobile-mega-drill__level is-active" data-mega-drill-level="families">
            @foreach($megaFamilies as $family)
              <button type="button"
                      class="shop-mobile-mega-drill__row"
                      data-mega-drill-open="family-{{ $family['id'] }}">
                <span class="shop-mobile-mega-drill__row-main">
                  @if(!empty($family['image']))
                    <img src="{{ $family['image'] }}" alt="" class="shop-mobile-mega-drill__thumb" width="28" height="28" loading="lazy">
                  @else
                    <span class="shop-mobile-mega-drill__thumb shop-mobile-mega-drill__thumb--fallback" aria-hidden="true"></span>
                  @endif
                  <span class="shop-mobile-mega-drill__label">{{ $family['name'] }}</span>
                </span>
                <svg class="shop-mobile-mega-drill__chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="m9 6 6 6-6 6"/>
                </svg>
              </button>
            @endforeach
          </div>

          @foreach($megaFamilies as $family)
            <div class="shop-mobile-mega-drill__level hidden"
                 data-mega-drill-level="family-{{ $family['id'] }}"
                 hidden>
              <a href="{{ $family['all_products_url'] }}"
                 class="shop-mobile-mega-drill__all-link"
                 onclick="toggleElement('mobileMenu', false)">همه محصولات {{ $family['name'] }}</a>
              @foreach($family['columns'] as $colIndex => $column)
                @if(!empty($column['items']))
                  <button type="button"
                          class="shop-mobile-mega-drill__row"
                          data-mega-drill-open="column-{{ $family['id'] }}-{{ $colIndex }}">
                    <span class="shop-mobile-mega-drill__row-main">
                      @if(!empty($column['image']))
                        <img src="{{ $column['image'] }}" alt="" class="shop-mobile-mega-drill__thumb" width="26" height="26" loading="lazy">
                      @else
                        <span class="shop-mobile-mega-drill__thumb shop-mobile-mega-drill__thumb--fallback" aria-hidden="true"></span>
                      @endif
                      <span class="shop-mobile-mega-drill__label">{{ $column['title'] }}</span>
                    </span>
                    <svg class="shop-mobile-mega-drill__chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                      <path d="m9 6 6 6-6 6"/>
                    </svg>
                  </button>
                @else
                  <a href="{{ $column['url'] }}"
                     class="shop-mobile-mega-drill__link"
                     onclick="toggleElement('mobileMenu', false)">{{ $column['title'] }}</a>
                @endif
              @endforeach
            </div>

            @foreach($family['columns'] as $colIndex => $column)
              @if(!empty($column['items']))
                <div class="shop-mobile-mega-drill__level hidden"
                     data-mega-drill-level="column-{{ $family['id'] }}-{{ $colIndex }}"
                     hidden>
                  <a href="{{ $column['url'] }}"
                     class="shop-mobile-mega-drill__heading-link"
                     onclick="toggleElement('mobileMenu', false)">{{ $column['title'] }}</a>
                  @foreach($column['items'] as $item)
                    <a href="{{ $item['url'] }}"
                       class="shop-mobile-mega-drill__item-link"
                       onclick="toggleElement('mobileMenu', false)">
                      @if(!empty($item['image']))
                        <img src="{{ $item['image'] }}" alt="" class="shop-mobile-mega-drill__item-icon" width="20" height="20" loading="lazy">
                      @endif
                      <span>{{ $item['label'] }}</span>
                    </a>
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
