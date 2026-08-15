<button type="button"
        wire:click="toggle"
        wire:loading.attr="disabled"
        class="product-wishlist-btn inline-flex items-center gap-1.5 text-sm font-bold transition-colors {{ $inWishlist ? 'text-red-500' : 'text-gray-500 hover:text-red-500' }}"
        aria-label="{{ $inWishlist ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' }}">
    <svg class="w-5 h-5" fill="{{ $inWishlist ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4.318 6.318a4.5 4.5 0 0 1 6.364 0L12 7.636l1.318-1.318a4.5 4.5 0 1 1 6.364 6.364L12 21.364 4.318 13.682a4.5 4.5 0 0 1 0-6.364z"/>
    </svg>
    <span wire:loading.remove wire:target="toggle">{{ $inWishlist ? 'در علاقه‌مندی‌ها' : 'علاقه‌مندی' }}</span>
    <span wire:loading wire:target="toggle">...</span>
</button>
