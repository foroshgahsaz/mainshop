<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\Category\CategoryDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CategoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_category_can_be_deleted_when_empty(): void
    {
        $general = Category::query()->create([
            'name' => 'عمومی',
            'slug' => 'general',
            'is_active' => true,
        ]);

        Category::query()->create([
            'name' => 'دیجیتال',
            'slug' => 'digital',
            'is_active' => true,
        ]);

        app(CategoryDeletionService::class)->delete($general);

        $this->assertDatabaseMissing('categories', ['id' => $general->id]);
        $this->assertDatabaseMissing('categories', ['slug' => 'general']);
    }

    public function test_deleting_general_does_not_recreate_it(): void
    {
        $general = Category::query()->create([
            'name' => 'عمومی',
            'slug' => 'general',
            'is_active' => true,
        ]);

        $other = Category::query()->create([
            'name' => 'دیجیتال',
            'slug' => 'digital',
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $general->id,
            'name' => 'محصول تست',
            'slug' => 'test-product',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        app(CategoryDeletionService::class)->delete($general);

        $this->assertDatabaseMissing('categories', ['slug' => 'general']);
        $this->assertDatabaseHas('categories', ['id' => $other->id]);
        $this->assertSame(1, Category::query()->count());
    }

    public function test_cannot_delete_last_category_when_products_have_orders(): void
    {
        $general = Category::query()->create([
            'name' => 'عمومی',
            'slug' => 'general',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $general->id,
            'name' => 'محصول سفارش‌دار',
            'slug' => 'ordered-product',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $product->orderItems()->create([
            'order_id' => $this->createOrderForProduct($product)->id,
            'quantity' => 1,
            'price' => 1000,
            'total_price' => 1000,
            'product_name' => $product->name,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ابتدا حداقل یک دسته دیگر ایجاد کنید');

        app(CategoryDeletionService::class)->delete($general);
    }

    protected function createOrderForProduct(Product $product): \App\Models\Order
    {
        $user = \App\Models\User::factory()->create();
        $address = \App\Models\UserAddress::query()->create([
            'user_id' => $user->id,
            'receiver_name' => 'علی',
            'receiver_phone' => '09121112233',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان تست',
            'postal_code' => '1234567890',
        ]);

        return \App\Models\Order::query()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'total_amount' => 1000,
            'final_amount' => 1000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'online',
            'status' => 'pending',
        ]);
    }
}
