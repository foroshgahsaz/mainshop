<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Services\Shop\MegaMenuBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MegaMenuBootstrapServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_assigns_roots_and_enables_family(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-service',
            'is_active' => true,
            'position' => 0,
        ]);

        $root = Category::query()->create([
            'name' => 'چینی پردیس',
            'slug' => 'pardis',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        $child = Category::query()->create([
            'parent_id' => $root->id,
            'name' => 'ست ۶ نفره',
            'slug' => 'set-6',
            'is_active' => true,
            'position' => 0,
        ]);

        Product::factory()->create([
            'category_id' => $child->id,
            'product_family_id' => $family->id,
        ]);

        $result = app(MegaMenuBootstrapService::class)->run();

        $family->refresh();
        $root->refresh();
        $child->refresh();

        $this->assertTrue($family->show_in_mega);
        $this->assertSame($family->id, $root->product_family_id);
        $this->assertSame($family->id, $child->product_family_id);
        $this->assertTrue($child->show_in_mega);
        $this->assertSame(1, $result['roots_updated']);
    }
}
