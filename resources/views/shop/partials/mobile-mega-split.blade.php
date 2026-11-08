@php
    $placeholderIcon = asset('shop/images/placeholder-category.svg');
@endphp

<div id="mobileMegaMenu"
     class="hidden shop-mobile-mega-split"
     data-mobile-mega-split
     dir="rtl">
    <aside class="shop-mobile-mega-split__rail" aria-label="دسته‌های اصلی">
        @foreach($megaFamilies as $index => $family)
            <button type="button"
                    class="shop-mobile-mega-split__rail-item {{ $index === 0 ? 'is-active' : '' }}"
                    data-mobile-mega-family-tab="{{ $family['id'] }}"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                <span class="shop-mobile-mega-split__rail-icon-wrap">
                    @if(!empty($family['image']))
                        <img src="{{ $family['image'] }}" alt="" class="shop-mobile-mega-split__rail-icon" width="32" height="32" loading="lazy">
                    @else
                        <img src="{{ $placeholderIcon }}" alt="" class="shop-mobile-mega-split__rail-icon" width="32" height="32" loading="lazy">
                    @endif
                </span>
                <span class="shop-mobile-mega-split__rail-label">{{ $family['name'] }}</span>
            </button>
        @endforeach
    </aside>

    <div class="shop-mobile-mega-split__panels">
        @foreach($megaFamilies as $index => $family)
            <div class="shop-mobile-mega-split__panel {{ $index === 0 ? 'is-active' : '' }}"
                 data-mobile-mega-family-panel="{{ $family['id'] }}"
                 @if($index !== 0) hidden @endif
                 role="tabpanel">
                <a href="{{ $family['all_products_url'] }}"
                   class="shop-mobile-mega-split__all-link"
                   onclick="toggleElement('mobileMenu', false)">
                    <span>همه محصولات دسته‌بندی {{ $family['name'] }}</span>
                    <svg class="shop-mobile-mega-split__all-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>

                @foreach($family['columns'] as $colIndex => $column)
                    @if(!empty($column['items']))
                        <section class="shop-mobile-mega-split__section {{ $colIndex === 0 ? 'is-expanded' : '' }}">
                            <button type="button"
                                    class="shop-mobile-mega-split__section-head"
                                    data-mobile-mega-section-toggle
                                    aria-expanded="{{ $colIndex === 0 ? 'true' : 'false' }}">
                                <span>{{ $column['title'] }}</span>
                                <svg class="shop-mobile-mega-split__section-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div class="shop-mobile-mega-split__section-body">
                                <div class="shop-mobile-mega-split__grid">
                                    @foreach($column['items'] as $item)
                                        <a href="{{ $item['url'] }}"
                                           class="shop-mobile-mega-split__tile"
                                           onclick="toggleElement('mobileMenu', false)">
                                            <span class="shop-mobile-mega-split__tile-icon-wrap">
                                                <img src="{{ !empty($item['image']) ? $item['image'] : $placeholderIcon }}"
                                                     alt=""
                                                     class="shop-mobile-mega-split__tile-icon"
                                                     width="56"
                                                     height="56"
                                                     loading="lazy">
                                            </span>
                                            <span class="shop-mobile-mega-split__tile-label">{{ $item['label'] }}</span>
                                        </a>
                                    @endforeach
                                    <a href="{{ $column['url'] }}"
                                       class="shop-mobile-mega-split__tile shop-mobile-mega-split__tile--all"
                                       onclick="toggleElement('mobileMenu', false)">
                                        <span class="shop-mobile-mega-split__tile-icon-wrap shop-mobile-mega-split__tile-icon-wrap--all" aria-hidden="true">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                                <circle cx="5" cy="5" r="1.6" fill="#94a3b8"/>
                                                <circle cx="12" cy="5" r="1.6" fill="#94a3b8"/>
                                                <circle cx="19" cy="5" r="1.6" fill="#94a3b8"/>
                                                <circle cx="5" cy="12" r="1.6" fill="#94a3b8"/>
                                                <circle cx="12" cy="12" r="1.6" fill="#94a3b8"/>
                                                <circle cx="19" cy="12" r="1.6" fill="#94a3b8"/>
                                                <circle cx="5" cy="19" r="1.6" fill="#94a3b8"/>
                                                <circle cx="12" cy="19" r="1.6" fill="#94a3b8"/>
                                                <circle cx="19" cy="19" r="1.6" fill="#94a3b8"/>
                                            </svg>
                                        </span>
                                        <span class="shop-mobile-mega-split__tile-label">همه {{ $column['title'] }}</span>
                                    </a>
                                </div>
                            </div>
                        </section>
                    @else
                        <a href="{{ $column['url'] }}"
                           class="shop-mobile-mega-split__flat-link"
                           onclick="toggleElement('mobileMenu', false)">
                            <span>{{ $column['title'] }}</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="m15 9-6 6 6 6" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</div>
