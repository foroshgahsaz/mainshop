<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\Settings\HomepageSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_product_ids_and_preserves_order(): void
    {
        $service = app(HomepageSettingsService::class);

        $service->saveFromAdminForm([
            'taxonomy_mode' => HomepageSettingsService::TAXONOMY_CATEGORIES,
            'new_products_enabled' => true,
            'new_product_ids' => '3، 1, 3',
        ]);

        $this->assertSame([3, 1], $service->newProductIds());
    }

    public function test_resolve_new_products_respects_id_order(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test-homepage-settings',
            'is_active' => true,
        ]);

        $p1 = Product::create([
            'category_id' => $category->id,
            'name' => 'One',
            'slug' => 'one',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $p2 = Product::create([
            'category_id' => $category->id,
            'name' => 'Two',
            'slug' => 'two',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $service = app(HomepageSettingsService::class);
        $service->saveFromAdminForm([
            'taxonomy_mode' => HomepageSettingsService::TAXONOMY_CATEGORIES,
            'new_products_enabled' => true,
            'new_product_ids' => (string) $p2->id.','.$p1->id,
        ]);

        $resolved = $service->resolveNewProducts(
            fn () => Product::query()->active()->with('images')
        );

        $this->assertSame([$p2->id, $p1->id], $resolved->pluck('id')->all());
    }

    public function test_new_products_disabled_returns_empty_collection(): void
    {
        $service = app(HomepageSettingsService::class);
        $service->saveFromAdminForm([
            'taxonomy_mode' => HomepageSettingsService::TAXONOMY_PRODUCT_FAMILIES,
            'new_products_enabled' => false,
            'new_product_ids' => '',
        ]);

        $this->assertFalse($service->newProductsEnabled());
        $this->assertSame(
            HomepageSettingsService::TAXONOMY_PRODUCT_FAMILIES,
            $service->taxonomyMode()
        );

        $resolved = $service->resolveNewProducts(
            fn () => Product::query()->active()->with('images')
        );

        $this->assertTrue($resolved->isEmpty());
    }
}
