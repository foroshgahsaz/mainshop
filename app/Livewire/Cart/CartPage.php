<?php

namespace App\Livewire\Cart;

use App\Services\Cart\CartService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('سبد خرید')]
class CartPage extends Component
{
    public function incrementQuantity(int $productId, ?int $variantId, CartService $cart): void
    {
        $item = $this->findItem($cart, $productId, $variantId);
        if (! $item) {
            return;
        }

        $this->updateQuantity($productId, min(99, $item['quantity'] + 1), $variantId, $cart);
    }

    public function decrementQuantity(int $productId, ?int $variantId, CartService $cart): void
    {
        $item = $this->findItem($cart, $productId, $variantId);
        if (! $item) {
            return;
        }

        $this->updateQuantity($productId, max(1, $item['quantity'] - 1), $variantId, $cart);
    }

    public function updateQuantity(int $productId, int $quantity, ?int $variantId, CartService $cart): void
    {
        try {
            $cart->update($productId, $quantity, $variantId);
            $this->dispatch('cart-updated');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function remove(int $productId, ?int $variantId, CartService $cart): void
    {
        $cart->remove($productId, $variantId);
        $this->dispatch('cart-updated');
        session()->flash('success', 'محصول از سبد حذف شد.');
    }

    protected function findItem(CartService $cart, int $productId, ?int $variantId): ?array
    {
        return $cart->getItems()->first(function (array $item) use ($productId, $variantId) {
            return (int) $item['product_id'] === $productId
                && ($item['product_variant_id'] ?? null) === $variantId;
        });
    }

    public function render(CartService $cart)
    {
        return view('livewire.cart.cart-page', [
            'items' => $cart->getItems(),
            'summary' => $cart->summary(),
        ]);
    }
}
