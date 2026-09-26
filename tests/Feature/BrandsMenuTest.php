<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandsMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_brands_menu_item_renders_active_brand_links(): void
    {
        MenuItem::query()->create([
            'label' => 'برندها',
            'item_type' => MenuItem::TYPE_BRANDS_TRIGGER,
            'link_type' => 'route',
            'link_value' => 'products.index',
            'location' => MenuItem::LOCATION_BOTH,
            'position' => 1,
            'is_active' => true,
        ]);

        $brand = Brand::query()->create([
            'name' => 'سامسونگ',
            'slug' => 'samsung',
            'is_active' => true,
            'position' => 1,
        ]);

        Brand::query()->create([
            'name' => 'غیرفعال',
            'slug' => 'inactive',
            'is_active' => false,
            'position' => 2,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('برندها', false);
        $response->assertSee('سامسونگ', false);
        $response->assertSee(route('brands.show', $brand), false);
        $response->assertDontSee(route('brands.show', 'inactive'), false);
    }
}
