<?php

namespace App\Services\Cart;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShoppingCart;
use App\Models\User;
use App\Services\Cache\ShopCacheService;
use App\Support\ShopFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        protected StockService $stockService,
        protected ShopCacheService $shopCache,
    ) {}

    public function getItems(): Collection
    {
        return $this->enrichItems($this->getRawItems());
    }

    public function add(int $productId, int $quantity = 1, ?int $variantId = null): void
    {
        $product = Product::active()->findOrFail($productId);
        $variant = $variantId ? ProductVariant::where('product_id', $productId)->findOrFail($variantId) : null;

        $this->stockService->assertAvailable($product, $variant, $quantity);

        $price = $variant ? $variant->effective_price : $product->effective_price;
        $sku = $variant?->sku ?? $product->sku;
        $name = $variant ? "{$product->name} ({$variant->name})" : $product->name;

        if ($user = Auth::user()) {
            $this->addToDatabase($user, $product, $variant, $quantity, $price, $name, $sku);

            return;
        }

        $this->addToGuestCart($productId, $variantId, $quantity, $price, $name, $sku);
    }

    public function update(int $productId, int $quantity, ?int $variantId = null): void
    {
        if ($quantity <= 0) {
            $this->remove($productId, $variantId);

            return;
        }

        $product = Product::active()->findOrFail($productId);
        $variant = $variantId ? ProductVariant::where('product_id', $productId)->findOrFail($variantId) : null;

        $this->stockService->assertAvailable($product, $variant, $quantity);

        if ($user = Auth::user()) {
            ShoppingCart::query()
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
                ->when(! $variantId, fn ($q) => $q->whereNull('product_variant_id'))
                ->update(['quantity' => $quantity]);

            return;
        }

        $cart = $this->getGuestCart();
        $key = $this->itemKey($productId, $variantId);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $quantity;
            $this->saveGuestCart($cart);
        }
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        if ($user = Auth::user()) {
            ShoppingCart::query()
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId))
                ->when(! $variantId, fn ($q) => $q->whereNull('product_variant_id'))
                ->delete();

            return;
        }

        $cart = $this->getGuestCart();
        unset($cart[$this->itemKey($productId, $variantId)]);
        $this->saveGuestCart($cart);
    }

    public function clear(): void
    {
        if ($user = Auth::user()) {
            ShoppingCart::where('user_id', $user->id)->delete();

            return;
        }

        Cache::forget($this->guestCacheKey());
    }

    public function subtotal(): int
    {
        return (int) $this->getRawItems()->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    public function count(): int
    {
        return (int) $this->getRawItems()->sum('quantity');
    }

    /** @return array{subtotal: int, discount: int, shipping: int, total: int, item_count: int, shipping_method: string|null} */
    public function summary(): array
    {
        $items = $this->getRawItems();
        $subtotal = (int) $items->sum(fn ($item) => $item['price'] * $item['quantity']);
        $itemCount = (int) $items->sum('quantity');
        $discount = $this->discountAmount();
        $afterDiscount = max(0, $subtotal - $discount);
        $shippingMethod = $this->shopCache->shippingMethods()->sortBy('price')->first();
        $shipping = $shippingMethod ? $shippingMethod->calculateCost($afterDiscount) : 0;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $afterDiscount + $shipping,
            'item_count' => $itemCount,
            'shipping_method' => $shippingMethod?->name,
        ];
    }

    public function discountAmount(): int
    {
        $code = session('cart.coupon_code');
        if (! $code) {
            return 0;
        }

        return 0;
    }

    public function mergeGuestCartIntoUser(User $user): void
    {
        $guestCart = $this->getGuestCart();

        if (empty($guestCart)) {
            return;
        }

        DB::transaction(function () use ($user, $guestCart) {
            foreach ($guestCart as $item) {
                try {
                    $this->addToDatabase(
                        $user,
                        Product::findOrFail($item['product_id']),
                        isset($item['product_variant_id']) ? ProductVariant::find($item['product_variant_id']) : null,
                        $item['quantity'],
                        $item['price'],
                        $item['product_name'],
                        $item['sku'] ?? null
                    );
                } catch (\Throwable) {
                    continue;
                }
            }
        });

        Cache::forget($this->guestCacheKey());
    }

    protected function getRawItems(): Collection
    {
        $user = Auth::user();

        return $user
            ? $this->getDatabaseItems($user)
            : collect($this->getGuestCart());
    }

    protected function getDatabaseItems(User $user): Collection
    {
        return ShoppingCart::query()
            ->select(['product_id', 'product_variant_id', 'quantity', 'price', 'product_name', 'sku'])
            ->where('user_id', $user->id)
            ->get()
            ->map(fn (ShoppingCart $item) => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
            ]);
    }

    protected function enrichItems(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $productIds = $items->pluck('product_id')->unique()->filter()->values();
        $products = Product::with(['images' => fn ($q) => $q->orderBy('position')])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return $items->map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);

            return [
                ...$item,
                'image' => ShopFormatter::productImage($product),
                'slug' => $product?->slug,
                'url' => $product ? route('products.show', $product) : route('products.index'),
            ];
        });
    }

    protected function addToDatabase(
        User $user,
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        int $price,
        string $name,
        ?string $sku
    ): void {
        $existing = ShoppingCart::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->when($variant, fn ($q) => $q->where('product_variant_id', $variant->id))
            ->when(! $variant, fn ($q) => $q->whereNull('product_variant_id'))
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            $this->stockService->assertAvailable($product, $variant, $newQty);
            $existing->update(['quantity' => $newQty]);

            return;
        }

        ShoppingCart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'price' => $price,
            'product_name' => $name,
            'sku' => $sku,
            'added_at' => now(),
        ]);
    }

    protected function addToGuestCart(
        int $productId,
        ?int $variantId,
        int $quantity,
        int $price,
        string $name,
        ?string $sku
    ): void {
        $cart = $this->getGuestCart();
        $key = $this->itemKey($productId, $variantId);

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $price,
                'product_name' => $name,
                'sku' => $sku,
            ];
        }

        $this->saveGuestCart($cart);
    }

    protected function getGuestCart(): array
    {
        return Cache::get($this->guestCacheKey(), []);
    }

    protected function saveGuestCart(array $cart): void
    {
        Cache::put($this->guestCacheKey(), $cart, now()->addMinutes(config('shop.cart.guest_ttl')));
    }

    protected function guestCacheKey(): string
    {
        return config('shop.cart.guest_prefix').session()->getId();
    }

    protected function itemKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? '0');
    }
}
