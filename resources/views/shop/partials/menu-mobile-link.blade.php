@props(['item'])

@php
    $url = $item->resolveUrl();
    $isOrders = $item->link_type === 'route' && $item->link_value === 'account.orders';
@endphp

<a href="{{ $isOrders && auth()->guest() ? '#' : $url }}"
   @if($isOrders && auth()->guest()) onclick="event.preventDefault(); toggleElement('loginModal', true)" @endif
   @if($item->open_in_new_tab) target="_blank" rel="noopener" @endif
   class="main-nav-link py-3 px-2 rounded-xl hover:bg-emerald-50 hover:text-brand-green">
    {{ $item->label }}
</a>
