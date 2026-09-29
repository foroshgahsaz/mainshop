<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductPlant;
use App\Models\ProductTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFamilyTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_belong_to_family_and_template(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-service',
            'is_active' => true,
            'position' => 0,
        ]);

        $brand = Brand::query()->create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'is_active' => true,
            'position' => 0,
        ]);

        $plant = ProductPlant::query()->create([
            'product_family_id' => $family->id,
            'name' => 'چینی پردیس',
            'slug' => 'chinese-pardis',
            'is_active' => true,
            'position' => 0,
        ]);

        $template = ProductTemplate::query()->create([
            'brand_id' => $brand->id,
            'product_family_id' => $family->id,
            'name' => 'مارشال',
            'slug' => 'marshal',
            'is_active' => true,
            'position' => 0,
        ]);

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'product_family_id' => $family->id,
            'product_plant_id' => $plant->id,
            'product_template_id' => $template->id,
        ]);

        $this->assertTrue($product->productFamily->is($family));
        $this->assertTrue($product->productPlant->is($plant));
        $this->assertTrue($product->productTemplate->is($template));
        $this->assertSame($brand->id, $product->productTemplate->brand_id);
    }
}
