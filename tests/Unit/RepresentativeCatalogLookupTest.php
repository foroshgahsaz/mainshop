<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Services\Representative\RepresentativeCatalogLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepresentativeCatalogLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_without_plants_lists_brands_and_products_by_family_and_brand_only(): void
    {
        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat-'.uniqid(),
            'is_active' => true,
        ]);
        $family = ProductFamily::query()->create([
            'name' => 'سرویس بلور',
            'slug' => 'blur-'.uniqid(),
            'is_active' => true,
            'position' => 1,
        ]);
        $brand = Brand::query()->create([
            'name' => 'HRT',
            'slug' => 'hrt-'.uniqid(),
        ]);
        Product::query()->create([
            'category_id' => $category->id,
            'product_family_id' => $family->id,
            'product_plant_id' => null,
            'product_template_id' => null,
            'brand_id' => $brand->id,
            'name' => 'لیوان بلور',
            'slug' => 'glass-'.uniqid(),
            'price' => 50000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $lookup = app(RepresentativeCatalogLookup::class);

        $this->assertFalse($lookup->familyHasPlants($family->id));
        $this->assertSame(['HRT'], $lookup->brands($family->id, null)->pluck('name')->all());
        $this->assertFalse($lookup->brandHasTemplates($family->id, null, $brand->id));

        $products = $lookup->products($family->id, null, $brand->id, null);
        $this->assertSame(1, $products->total());
        $this->assertSame('لیوان بلور', $products->items()[0]->name);
    }
}
