<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Product\ProductDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDeletionRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_restore_soft_deleted_product(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => true]);
        $category = Category::query()->forceCreate([
            'name' => 'دسته',
            'slug' => 'cat-restore',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'name' => 'محصول',
            'slug' => 'product-restore',
            'sku' => 'SKU-RESTORE',
            'price' => 1000,
            'stock' => 1,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $product->delete();

        $this->actingAs($admin);
        app(ProductDeletionService::class)->restore($product->fresh());

        $this->assertNull($product->fresh()->deleted_at);
    }

    public function test_force_delete_removes_product_without_orders(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => true]);
        $category = Category::query()->forceCreate([
            'name' => 'دسته۲',
            'slug' => 'cat-force',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'name' => 'محصول',
            'slug' => 'product-force',
            'sku' => 'SKU-FORCE',
            'price' => 1000,
            'stock' => 1,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin);
        $id = $product->id;
        app(ProductDeletionService::class)->forceDeleteForever($product);

        $this->assertNull(Product::query()->withTrashed()->find($id));
    }
}