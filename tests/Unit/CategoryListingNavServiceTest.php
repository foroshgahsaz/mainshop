<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\Shop\CategoryListingNavService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryListingNavServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_breadcrumb_trail_from_root_to_leaf(): void
    {
        $root = Category::query()->create([
            'name' => 'چینی ، بلور ، اپال',
            'slug' => 'china-blur-opal',
            'is_active' => true,
            'position' => 0,
        ]);
        $mid = Category::query()->create([
            'name' => 'اپال',
            'slug' => 'opal',
            'parent_id' => $root->id,
            'is_active' => true,
            'position' => 0,
        ]);
        $leaf = Category::query()->create([
            'name' => 'فالکن',
            'slug' => 'falcon',
            'parent_id' => $mid->id,
            'is_active' => true,
            'position' => 0,
        ]);

        $trail = app(CategoryListingNavService::class)->breadcrumbTrail($leaf);

        $this->assertSame(['چینی ، بلور ، اپال', 'اپال', 'فالکن'], array_column($trail, 'name'));
    }

    public function test_carousel_only_lists_direct_children(): void
    {
        $root = Category::query()->create([
            'name' => 'ریشه',
            'slug' => 'root-nav',
            'is_active' => true,
            'position' => 0,
        ]);
        $child = Category::query()->create([
            'name' => 'فرزند',
            'slug' => 'child-nav',
            'parent_id' => $root->id,
            'is_active' => true,
            'position' => 0,
        ]);

        $nav = app(CategoryListingNavService::class);
        $this->assertCount(1, $nav->carouselCategories($root));
        $this->assertCount(0, $nav->carouselCategories($child));
    }
}
