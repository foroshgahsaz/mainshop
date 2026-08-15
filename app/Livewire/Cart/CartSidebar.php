<?php

namespace App\Livewire\Cart;

use App\Services\Cart\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartSidebar extends Component
{
    public bool $loaded = false;

    #[On('load-cart-sidebar')]
    public function loadSidebar(): void
    {
        $this->loaded = true;
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        $this->loaded = true;
    }

    public function incrementQuantity(int $productId, ?int $variantId, CartService $cart): void
    {
        $this->loaded = true;
        $item = $this->findItem($cart, $productId, $variantId);
        if (! $item) {
            return;
        }

        $this->updateQuantity($productId, min(99, $item['quantity'] + 1), $variantId, $cart);
    }

    public function decrementQuantity(int $productId, ?int $variantId, CartService $cart): void
    {
        $this->loaded = true;
        $item = $this->findItem($cart, $productId, $variantId);
        if (! $item) {
            return;
        }

        $this->updateQuantity($productId, max(1, $item['quantity'] - 1), $variantId, $cart);
    }

    public function updateQuantity(int $productId, int $quantity, ?int $variantId, CartService $cart): void
    {
        $this->loaded = true;

        try {
            $cart->update($productId, $quantity, $variantId);
            $this->dispatch('cart-updated');
        } catch (\RuntimeException $e) {
            $this->dispatch('cart-error', message: $e->getMessage());
        }
    }

    public function remove(int $productId, ?int $variantId, CartService $cart): void
    {
        $this->loaded = true;
        $cart->remove($productId, $variantId);
        $this->dispatch('cart-updated');
    }

    protected function findItem(CartService $cart, int $productId, ?int $variantId): ?array
    {
        return $cart->getItems()->first(function (array $item) use ($productId, $variantId) {
            $sameProduct = (int) $item['product_id'] === $productId;
            $itemVariant = $item['product_variant_id'] ?? null;

            return $sameProduct && $itemVariant === $variantId;
        });
    }

    public function render(CartService $cart)
    {
        if (! $this->loaded) {
            return view('livewire.cart.cart-sidebar-placeholder');
        }

        return view('livewire.cart.cart-sidebar', [
            'items' => $cart->getItems(),
            'summary' => $cart->summary(),
        ]);
    }
}
