<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\Shop\CategoryPathResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPathResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_and_resolves_nested_path(): void
    {
        $root = Category::query()->create([
            'name' => 'چینی ، بلور ، اپال',
            'slug' => 'opal-root',
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
            'name' => 'پارس اپال',
            'slug' => 'pars-apal',
            'parent_id' => $mid->id,
            'is_active' => true,
            'position' => 0,
        ]);

        $resolver = app(CategoryPathResolver::class);

        $this->assertSame('opal-root/opal/pars-apal', $resolver->pathFor($leaf));
        $this->assertTrue($leaf->is($resolver->resolve('opal-root/opal/pars-apal')));
        $this->assertTrue($leaf->is($resolver->resolve('pars-apal')));
    }

    public function test_breadcrumb_trail_builds_incremental_paths(): void
    {
        $root = Category::query()->create([
            'name' => 'ریشه',
            'slug' => 'root',
            'is_active' => true,
            'position' => 0,
        ]);
        $leaf = Category::query()->create([
            'name' => 'برگ',
            'slug' => 'leaf',
            'parent_id' => $root->id,
            'is_active' => true,
            'position' => 0,
        ]);

        $trail = app(CategoryPathResolver::class)->breadcrumbTrail($leaf);

        $this->assertSame(['root', 'root/leaf'], array_column($trail, 'path'));
    }

    public function test_attach_listing_paths_without_extra_queries_in_route_key(): void
    {
        $root = Category::query()->create([
            'name' => 'ریشه',
            'slug' => 'r',
            'is_active' => true,
            'position' => 0,
        ]);
        $leaf = Category::query()->create([
            'name' => 'برگ',
            'slug' => 'l',
            'parent_id' => $root->id,
            'is_active' => true,
            'position' => 0,
        ]);

        $collection = Category::query()->whereIn('id', [$root->id, $leaf->id])->get(['id', 'name', 'slug', 'parent_id']);
        app(CategoryPathResolver::class)->attachListingPaths($collection);

        $resolved = $collection->firstWhere('id', $leaf->id);
        $this->assertSame('r/l', $resolved->getRouteKey());
    }
}
