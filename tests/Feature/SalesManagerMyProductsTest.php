<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesManagerMyProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_manager_sees_only_own_created_products_on_mine_list(): void
    {
        $manager = User::factory()->create([
            'is_sales_manager' => true,
            'is_admin' => false,
            'status' => true,
        ]);
        $other = User::factory()->create([
            'is_sales_manager' => true,
            'status' => true,
        ]);

        $category = Category::query()->forceCreate([
            'name' => 'دسته',
            'slug' => 'cat-mine',
            'is_active' => true,
        ]);
        $brand = Brand::query()->forceCreate([
            'name' => 'برند',
            'slug' => 'brand-mine',
            'is_active' => true,
        ]);

        $mine = Product::query()->create([
            'name' => 'محصول من',
            'slug' => 'my-product',
            'price' => 1000,
            'stock' => 1,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'created_by_user_id' => $manager->id,
            'last_edited_by_user_id' => $manager->id,
            'is_active' => true,
        ]);

        Product::query()->create([
            'name' => 'محصول دیگری',
            'slug' => 'other-product',
            'price' => 1000,
            'stock' => 1,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'created_by_user_id' => $other->id,
            'is_active' => true,
        ]);

        $this->actingAs($manager);

        $response = $this->get(ProductResource::getUrl('mine'));
        $response->assertOk();
        $response->assertSee('محصول من');
        $response->assertDontSee('محصول دیگری');

        $this->assertSame((int) $manager->id, (int) $mine->fresh()->created_by_user_id);
    }

    public function test_bankdar_cannot_open_my_products_page(): void
    {
        $bankdar = User::factory()->create([
            'is_bankdar' => true,
            'bankdar_province_id' => 1,
            'status' => true,
        ]);

        $this->actingAs($bankdar);

        $this->get(ProductResource::getUrl('mine'))->assertForbidden();
    }
}