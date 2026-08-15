<?php

namespace App\Livewire\Account;

use App\Models\Product;
use App\Models\Wishlist;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('علاقه‌مندی‌ها')]
class WishlistPage extends Component
{
    public function remove(int $productId): void
    {
        auth()->user()->wishlists()->where('product_id', $productId)->delete();
        session()->flash('success', 'از علاقه‌مندی‌ها حذف شد.');
    }

    public function render()
    {
        $items = auth()->user()->wishlists()->with('product.images')->latest()->get();

        return view('livewire.account.wishlist-page', compact('items'));
    }
}
