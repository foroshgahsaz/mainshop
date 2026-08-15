@php
    $popularSearches = ['پیراهن', 'کفش', 'پوشاک', 'چاپینو'];
    $megaColumns = ($navCategories ?? collect())->take(3);
@endphp

<!-- MEGA MENU backdrop + panel (desktop) -->
<div id="megaMenuBackdrop" class="shop-mega-backdrop"></div>
<div id="megaMenuBox"
     class="shop-mega-menu fixed right-0 left-0 w-screen bg-white border-t border-gray-100 shadow-lg"
     aria-hidden="true">
  <div class="max-w-site mx-auto px-6 py-8 grid grid-cols-4 gap-8">
    @forelse($megaColumns as $cat)
      <div @if($cat->mega_column) data-mega-column="{{ $cat->mega_column }}" @endif>
        <h4 class="font-bold text-gray-900 border-b pb-2 mb-3 text-sm">
          <a href="{{ route('categories.show', $cat) }}" class="hover:text-brand-green">{{ $cat->name }}</a>
        </h4>
        <ul class="flex flex-col gap-2 text-gray-500 text-xs">
          @foreach($cat->children->take(5) as $child)
            <li><a href="{{ route('categories.show', $child) }}" class="hover:text-brand-green">{{ $child->name }}</a></li>
          @endforeach
          @if($cat->children->isEmpty())
            <li><a href="{{ route('categories.show', $cat) }}" class="hover:text-brand-green">مشاهده همه</a></li>
          @endif
        </ul>
      </div>
    @empty
      <div class="col-span-3 text-sm text-gray-500">دسته‌بندی‌ای ثبت نشده است.</div>
    @endforelse
    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 p-4 rounded-xl">
      @php $megaPromo = $navigation['megaPromo'] ?? null; @endphp
      <span class="text-brand-green font-bold text-sm block mb-1">{{ $megaPromo?->label ?? 'فروش ویژه' }}</span>
      <p class="text-gray-500 text-[11px] leading-relaxed mb-4">بهترین محصولات با قیمت استثنایی</p>
      <a href="{{ $megaPromo ? $megaPromo->resolveUrl() : route('products.index') }}"
         class="text-xs text-white bg-brand-green py-2 px-3 rounded-lg block text-center font-bold">مشاهده همه</a>
    </div>
  </div>
</div>

<!-- SEARCH MODAL -->
<div id="searchModal" class="search-modal" aria-hidden="true">
  <div class="search-modal__panel">
    <div class="search-modal__header">
      <button type="button" class="search-modal__close" onclick="toggleSearchModal(false)" aria-label="بستن">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M5 12h14M12 5l7 7-7 7"/>
        </svg>
      </button>
      <form action="{{ route('products.index') }}" method="GET" class="search-modal__form flex-1">
        <input type="text" name="search" value="{{ request('search') }}"
               class="search-modal__input"
               placeholder="جستجو در تمام محصولات چاپینو..."
               autocomplete="off">
        <svg class="search-modal__icon w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
        </svg>
      </form>
    </div>

    <div class="search-modal__body">
      <div>
        <p class="search-modal__section-title">
          <svg class="w-3.5 h-3.5 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 23c-1.1 0-2-.9-2-2h4c0 1.1-.9 2-2 2zm6-6H6V9c0-3.31 2.69-6 6-6s6 2.69 6 6v8z"/>
          </svg>
          جستجوهای پر تکرار
        </p>
        <div class="search-modal__tags">
          @foreach($popularSearches as $term)
            <a href="{{ route('products.index', ['search' => $term]) }}"
               class="search-modal__tag"
               onclick="toggleSearchModal(false)">{{ $term }}</a>
          @endforeach
        </div>
      </div>

      <div class="search-modal__promo">
        <div>
          <p class="search-modal__promo-text">فروش ویژه</p>
          <p class="search-modal__promo-title">تخفیف‌های شگفت‌انگیز چاپینو</p>
        </div>
        <a href="{{ route('products.index') }}" class="search-modal__promo-btn" onclick="toggleSearchModal(false)">مشاهده کالاها</a>
      </div>

      @if($searchCategories->isNotEmpty())
        <div>
          <p class="search-modal__section-title">
            <svg class="w-3.5 h-3.5 text-brand-green" fill="currentColor" viewBox="0 0 24 24">
              <path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/>
            </svg>
            دسته‌بندی‌ها
          </p>
          <div class="search-modal__categories">
            @foreach($searchCategories->take(6) as $cat)
              <a href="{{ route('categories.show', $cat) }}" class="search-modal__category" onclick="toggleSearchModal(false)">
                <span class="search-modal__category-icon">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 7h16M4 12h10M4 17h6"/>
                  </svg>
                </span>
                {{ $cat->name }}
              </a>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>
</div>
