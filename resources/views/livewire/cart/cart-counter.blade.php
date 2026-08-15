<button type="button"
        data-open-cart
        class="header-icon-btn relative"
        aria-label="سبد خرید">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
        <path d="M3 6h18" />
        <path d="M16 10a4 4 0 0 1-8 0" />
    </svg>
    @if ($count > 0)
        <span class="cart-badge">{{ $count }}</span>
    @endif
</button>
