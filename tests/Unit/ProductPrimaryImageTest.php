<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPrimaryImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_image_path_uses_is_primary_not_first_position(): void
    {
        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat-'.uniqid(),
            'is_active' => true,
        ]);
        $family = ProductFamily::query()->create([
            'name' => 'Fam',
            'slug' => 'fam-'.uniqid(),
            'is_active' => true,
            'position' => 1,
        ]);
        $brand = Brand::query()->create([
            'name' => 'Brand',
            'slug' => 'brand-'.uniqid(),
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'product_family_id' => $family->id,
            'brand_id' => $brand->id,
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'uploads/promo-banner.jpg',
            'is_primary' => false,
            'position' => 0,
        ]);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'image' => 'uploads/main-product.jpg',
            'is_primary' => true,
            'position' => 1,
        ]);

        $product->load(['images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position')]);

        $this->assertSame('uploads/main-product.jpg', $product->primary_image_path);
    }
}
