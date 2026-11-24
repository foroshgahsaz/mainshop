<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockNullSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_product_with_null_stock_persists_zero(): void
    {
        $category = Category::query()->forceCreate([
            'name' => 'دسته',
            'slug' => 'cat-stock-null',
            'is_active' => true,
        ]);
        $brand = Brand::query()->forceCreate([
            'name' => 'برند',
            'slug' => 'brand-stock-null',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'name' => 'محصول',
            'slug' => 'product-stock-null',
            'price' => 1000,
            'stock' => 12,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $product->stock = null;
        $product->save();

        $this->assertSame(0, $product->fresh()->stock);
    }
}