<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Product\ProductSkuLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSkuLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_soft_deleted_product_by_sku(): void
    {
        $category = Category::query()->forceCreate([
            'name' => 'دسته',
            'slug' => 'cat-sku-lookup',
            'is_active' => true,
        ]);
        $brand = Brand::query()->forceCreate([
            'name' => 'برند',
            'slug' => 'brand-sku-lookup',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'محصول مخفی',
            'slug' => 'hidden-sku-product',
            'sku' => '01040102906014',
            'price' => 1000,
            'stock' => 1,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => false,
        ]);
        $product->delete();

        $hits = app(ProductSkuLookupService::class)->find('01040102906014');

        $this->assertCount(1, $hits);
        $this->assertSame('product', $hits[0]['entity']);
        $this->assertSame((int) $product->id, $hits[0]['product_id']);
        $this->assertTrue($hits[0]['deleted']);
    }

    public function test_finds_variant_sku(): void
    {
        $category = Category::query()->forceCreate([
            'name' => 'دسته۲',
            'slug' => 'cat-sku-lookup-2',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'name' => 'محصول با تنوع',
            'slug' => 'variant-sku-product',
            'sku' => 'MAIN-SKU',
            'price' => 1000,
            'stock' => 1,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'قرمز',
            'price' => 1000,
            'stock' => 1,
            'sku' => '01040102906014',
            'is_active' => true,
        ]);

        $hits = app(ProductSkuLookupService::class)->find('01040102906014');

        $this->assertCount(1, $hits);
        $this->assertSame('variant', $hits[0]['entity']);
    }

    public function test_normalizes_spaces_in_sku(): void
    {
        $lookup = app(ProductSkuLookupService::class);
        $this->assertSame('01040102906014', $lookup->normalizeSku(' 0104 0102 906014 '));
    }
}