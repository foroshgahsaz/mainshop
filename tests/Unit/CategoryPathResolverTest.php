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
}
