<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\Shop\CategoryTaxonomySeederService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTaxonomySeederServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rebuilds_tree_with_mega_roots_and_removes_legacy_categories(): void
    {
        $legacy = Category::query()->create([
            'name' => 'دسته قدیمی',
            'slug' => 'legacy-cat',
            'is_active' => true,
            'position' => 0,
        ]);

        Product::query()->create([
            'name' => 'محصول تست',
            'slug' => 'test-product-cat-seed',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
            'category_id' => $legacy->id,
        ]);

        $result = app(CategoryTaxonomySeederService::class)->run();

        $this->assertSame(0, Category::query()->whereKey($legacy->id)->count());
        $this->assertSame(9, Category::query()->whereNull('parent_id')->count());
        $this->assertGreaterThan(20, $result['created']);
        $this->assertTrue(Category::query()->where('name', 'چینی ، بلور ، اپال')->where('show_in_mega', true)->exists());
        $this->assertTrue(Category::query()->where('name', 'سرویس غذاخوری')->where('show_in_mega', true)->exists());
    }
}
