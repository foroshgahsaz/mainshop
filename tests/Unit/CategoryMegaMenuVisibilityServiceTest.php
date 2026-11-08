<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\ProductFamily;
use App\Services\Shop\CategoryMegaMenuVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryMegaMenuVisibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_enables_show_in_mega_on_mapped_root_and_children(): void
    {
        $root = Category::query()->create([
            'name' => 'ریشه تست',
            'slug' => 'test-root-mega',
            'is_active' => true,
            'show_in_mega' => false,
            'position' => 0,
        ]);

        $column = Category::query()->create([
            'name' => 'ستون',
            'slug' => 'test-column-mega',
            'parent_id' => $root->id,
            'is_active' => true,
            'show_in_mega' => false,
            'position' => 0,
        ]);

        $family = ProductFamily::query()->create([
            'name' => 'ریشه تست',
            'slug' => 'test-root-mega-family',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        Category::query()->whereKey($root->id)->update(['product_family_id' => $family->id]);

        $result = app(CategoryMegaMenuVisibilityService::class)->syncFromMappedFamilies();

        $this->assertContains($root->id, $result['roots']);
        $this->assertGreaterThanOrEqual(2, $result['categories_enabled']);

        $root->refresh();
        $column->refresh();

        $this->assertTrue($root->show_in_mega);
        $this->assertTrue($column->show_in_mega);
    }
}
