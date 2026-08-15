<?php

namespace App\Livewire\Product;

use App\Models\Product;
use App\Models\Wishlist;
use Livewire\Component;

class ToggleWishlist extends Component
{
    public Product $product;

    public bool $inWishlist = false;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->refreshState();
    }

    public function toggle(): void
    {
        if (! auth()->check()) {
            $this->dispatch('open-login-modal');

            return;
        }

        $existing = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $this->product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            session()->flash('wishlist_message', 'از علاقه‌مندی‌ها حذف شد.');
        } else {
            Wishlist::create([
                'user_id' => auth()->id(),
                'product_id' => $this->product->id,
            ]);
            session()->flash('wishlist_message', 'به علاقه‌مندی‌ها اضافه شد.');
        }

        $this->refreshState();
    }

    protected function refreshState(): void
    {
        $this->inWishlist = auth()->check() && Wishlist::where('user_id', auth()->id())
            ->where('product_id', $this->product->id)
            ->exists();
    }

    public function render()
    {
        return view('livewire.product.toggle-wishlist');
    }
}
