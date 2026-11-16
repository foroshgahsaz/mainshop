<?php

namespace Tests\Feature;

use App\Livewire\Product\AddToCart;
use App\Models\Category;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AddToCartStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_product_returns_friendly_message_not_model_not_found(): void
    {
        $product = $this->makeProduct(['is_active' => false, 'stock' => 5]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('این محصول فعلاً قابل خرید نیست.');

        app(CartService::class)->add($product->id, 1);
    }

    public function test_missing_product_returns_friendly_message(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('این محصول در فروشگاه یافت نشد.');

        app(CartService::class)->add(2279, 1);
    }

    public function test_add_to_cart_refreshes_stale_stock_and_shows_insufficient(): void
    {
        $product = $this->makeProduct(['is_active' => true, 'stock' => 5]);

        $component = Livewire::test(AddToCart::class, ['product' => $product])
            ->assertSet('selectedStock', 5);

        $product->update(['stock' => 0]);

        $component
            ->call('add')
            ->assertSet('selectedStock', 0)
            ->assertSessionHas('error', 'موجودی کافی نیست.');

        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_guest_cart_cannot_exceed_stock_across_multiple_adds(): void
    {
        $product = $this->makeProduct(['is_active' => true, 'stock' => 1]);

        app(CartService::class)->add($product->id, 1);
        $this->assertSame(1, app(CartService::class)->count());

        try {
            app(CartService::class)->add($product->id, 1);
            $this->fail('Expected stock exception');
        } catch (RuntimeException $e) {
            $this->assertSame('موجودی کافی نیست.', $e->getMessage());
        }

        $this->assertSame(1, app(CartService::class)->count());
    }

    /** @param  array<string, mixed>  $overrides */
    protected function makeProduct(array $overrides = []): Product
    {
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
        ]);

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'Product',
            'slug' => 'product-'.uniqid(),
            'price' => 100000,
            'stock' => 5,
            'is_active' => true,
        ], $overrides));
    }
}
