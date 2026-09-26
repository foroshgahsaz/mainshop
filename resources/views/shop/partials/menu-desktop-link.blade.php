@props(['item'])

@php
    $url = $item->resolveUrl();
    $isOrders = $item->link_type === 'route' && $item->link_value === 'account.orders';
@endphp

@if ($item->item_type === \App\Models\MenuItem::TYPE_BRANDS_TRIGGER)
    <div class="shop-nav-brands py-1" data-brands-nav>
        <button type="button"
                class="main-nav-link flex items-center gap-1.5 hover:text-brand-green"
                aria-expanded="false"
                aria-haspopup="true">
            {{ $item->label }}
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </button>
        <div class="shop-nav-brands__panel hidden" role="menu" aria-hidden="true">
            <div class="shop-nav-brands__panel-inner">
                @forelse(($navBrands ?? collect()) as $brand)
                    <a href="{{ route('brands.show', $brand) }}"
                       class="shop-nav-brands__link"
                       role="menuitem">{{ $brand->name }}</a>
                @empty
                    <span class="shop-nav-brands__empty">برندی ثبت نشده است.</span>
                @endforelse
            </div>
        </div>
    </div>
@elseif ($item->item_type === \App\Models\MenuItem::TYPE_MEGA_TRIGGER)
    <div class="relative py-1" id="megaMenuContainer">
        <a href="{{ $url }}"
           class="main-nav-link flex items-center gap-1.5 hover:text-brand-green">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/>
            </svg>
            {{ $item->label }}
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </a>
    </div>
@else
    <a href="{{ $isOrders && auth()->guest() ? '#' : $url }}"
       @if($isOrders && auth()->guest()) data-open-login onclick="event.preventDefault(); window.openLoginModal && window.openLoginModal('{{ route('account.orders') }}')" @endif
       @if($item->open_in_new_tab) target="_blank" rel="noopener" @endif
       class="main-nav-link flex items-center gap-1.5 hover:text-brand-green">
        {{ $item->label }}
    </a>
@endif
