<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProductNotForSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_for_sale_product_cannot_be_added_to_cart(): void
    {
        $category = Category::query()->create([
            'name' => 'تست',
            'slug' => 'test-nfs-'.uniqid(),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'محصول غیرقابل فروش',
            'slug' => 'nfs-'.uniqid(),
            'is_active' => true,
            'is_not_for_sale' => true,
            'stock' => 10,
            'price' => 100000,
        ]);

        $this->assertTrue($product->isNotForSale());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('غیر قابل فروش');

        app(CartService::class)->add($product->id, 1);
    }

    public function test_sellable_product_can_be_added_to_cart(): void
    {
        $category = Category::query()->create([
            'name' => 'تست',
            'slug' => 'test-sale-'.uniqid(),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'محصول قابل فروش',
            'slug' => 'sale-'.uniqid(),
            'is_active' => true,
            'is_not_for_sale' => false,
            'stock' => 10,
            'price' => 100000,
        ]);

        app(CartService::class)->add($product->id, 1);

        $this->assertNotEmpty(app(CartService::class)->getItems());
    }
}
