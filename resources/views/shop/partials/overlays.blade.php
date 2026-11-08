@php
    use App\Support\ShopFormatter;

    $megaMenu = $megaMenu ?? ['has_families' => false, 'families' => []];
    $megaFamilies = $megaMenu['families'] ?? [];
    $megaColumns = ($navCategories ?? collect())->take(3);
    $searchPopup = $searchPopup ?? [];
    $popularSearches = app(\App\Services\Settings\SearchPopupSettingsService::class)->popularSearchLinks();
    $searchPlaceholder = $searchPopup['placeholder'] ?? ('جستجو در تمام محصولات '.site_name().'...');
    $showSearchCategories = (bool) ($searchPopup['show_categories'] ?? true);
    $searchBanner = $searchPopup['banner'] ?? [];
    $bannerEnabled = (bool) ($searchBanner['enabled'] ?? false);
    $bannerImage = filled($searchBanner['image'] ?? null)
        ? ShopFormatter::storageImageUrl($searchBanner['image'])
        : null;
@endphp

<!-- MEGA MENU backdrop + panel (desktop) -->
<div id="megaMenuBackdrop" class="shop-mega-backdrop"></div>
<div id="megaMenuBox"
     class="shop-mega-menu fixed right-0 left-0 w-screen bg-white border-t border-gray-100 shadow-lg {{ !empty($megaMenu['has_families']) ? 'shop-mega-menu--families' : '' }}"
     aria-hidden="true">
  <div class="max-w-site mx-auto px-4 md:px-6 py-6">
    @if(!empty($megaMenu['has_families']) && $megaFamilies !== [])
      <div class="shop-mega-families" data-mega-families>
        <aside class="shop-mega-families__rail" aria-label="خانواده محصول">
          <ul class="shop-mega-families__rail-list" role="tablist">
            @foreach($megaFamilies as $index => $family)
              <li role="presentation">
                <button type="button"
                        class="shop-mega-families__rail-btn {{ $index === 0 ? 'is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                        data-mega-family-tab="{{ $family['id'] }}">
                  <span class="shop-mega-families__rail-label">
                    @if(!empty($family['image']))
                      <img src="{{ $family['image'] }}" alt="" class="shop-mega-families__rail-icon" width="24" height="24" loading="lazy">
                    @endif
                    <span>{{ $family['name'] }}</span>
                  </span>
                  <svg class="shop-mega-families__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </li>
            @endforeach
          </ul>
        </aside>
        <div class="shop-mega-families__panels">
          @foreach($megaFamilies as $index => $family)
            <div class="shop-mega-families__panel {{ $index === 0 ? 'is-active' : '' }}"
                 role="tabpanel"
                 data-mega-family-panel="{{ $family['id'] }}"
                 @if($index !== 0) hidden @endif>
              <a href="{{ $family['all_products_url'] }}" class="shop-mega-families__all-link">
                همه محصولات {{ $family['name'] }}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <div class="shop-mega-families__columns">
                @forelse($family['columns'] as $column)
                  <div class="shop-mega-families__column">
                    <h4 class="shop-mega-families__column-title">
                      <a href="{{ $column['url'] }}" class="shop-mega-families__column-title-link">
                        @if(!empty($column['image']))
                          <img src="{{ $column['image'] }}" alt="" class="shop-mega-families__column-icon" width="22" height="22" loading="lazy">
                        @endif
                        <span>{{ $column['title'] }}</span>
                      </a>
                    </h4>
                    @if(!empty($column['items']))
                      <ul class="shop-mega-families__column-links">
                        @foreach($column['items'] as $item)
                          <li>
                            <a href="{{ $item['url'] }}" class="shop-mega-families__item-link">
                              @if(!empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="" class="shop-mega-families__item-icon" width="18" height="18" loading="lazy">
                              @endif
                              <span>{{ $item['label'] }}</span>
                            </a>
                          </li>
                        @endforeach
                      </ul>
                    @endif
                  </div>
                @empty
                  <p class="shop-mega-families__empty text-sm text-gray-500">در حال بارگزاری محصول</p>
                @endforelse
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @else
      <div class="grid grid-cols-4 gap-8">
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
    @endif
  </div>
</div>

<!-- SEARCH MODAL -->
<div id="searchModal"
     class="search-modal"
     aria-hidden="true"
     data-suggest-url="{{ route('shop.search.suggest') }}"
     data-min-chars="3">
  <div class="search-modal__panel">
    <div class="search-modal__header">
      <button type="button" class="search-modal__close" onclick="toggleSearchModal(false)" aria-label="بستن">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M5 12h14M12 5l7 7-7 7"/>
        </svg>
      </button>
      <form action="{{ route('products.index') }}" method="GET" class="search-modal__form flex-1" id="searchModalForm">
        <input type="text"
               name="search"
               id="searchModalInput"
               value="{{ request('search') }}"
               class="search-modal__input"
               placeholder="{{ $searchPlaceholder }}"
               autocomplete="off"
               aria-autocomplete="list"
               aria-controls="searchModalLiveResults">
        <svg class="search-modal__icon w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
        </svg>
      </form>
    </div>

    <div class="search-modal__body">
      <div id="searchModalLiveResults" class="search-modal__live hidden" aria-live="polite"></div>

      <div id="searchModalDefaultContent" class="search-modal__default">
        @if(count($popularSearches) > 0)
          <div>
            <p class="search-modal__section-title">
              <svg class="w-3.5 h-3.5 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 23c-1.1 0-2-.9-2-2h4c0 1.1-.9 2-2 2zm6-6H6V9c0-3.31 2.69-6 6-6s6 2.69 6 6v8z"/>
              </svg>
              جستجوهای پر تکرار
            </p>
            <div class="search-modal__tags">
              @foreach($popularSearches as $item)
                <a href="{{ $item['url'] }}"
                   class="search-modal__tag"
                   onclick="toggleSearchModal(false)">{{ $item['term'] }}</a>
              @endforeach
            </div>
          </div>
        @endif

        @if($bannerEnabled)
          <div class="search-modal__promo @if($bannerImage) search-modal__promo--image @endif">
            @if($bannerImage)
              <img src="{{ $bannerImage }}" alt="" class="search-modal__promo-img" loading="lazy">
            @endif
            <div class="search-modal__promo-content">
              @if(filled($searchBanner['subtitle'] ?? null))
                <p class="search-modal__promo-text">{{ $searchBanner['subtitle'] }}</p>
              @endif
              @if(filled($searchBanner['title'] ?? null))
                <p class="search-modal__promo-title">{{ $searchBanner['title'] }}</p>
              @endif
            </div>
            @if(filled($searchBanner['link'] ?? null))
              <a href="{{ $searchBanner['link'] }}"
                 class="search-modal__promo-btn"
                 onclick="toggleSearchModal(false)">{{ $searchBanner['button_text'] ?? 'مشاهده' }}</a>
            @endif
          </div>
        @endif

        @if($showSearchCategories && ($searchCategories ?? collect())->isNotEmpty())
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
</div>
