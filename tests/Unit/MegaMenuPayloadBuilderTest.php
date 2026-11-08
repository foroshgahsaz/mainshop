<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\MegaMenuLink;
use App\Models\ProductFamily;
use App\Services\Shop\MegaMenuPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MegaMenuPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_when_no_families_in_mega(): void
    {
        ProductFamily::query()->create([
            'name' => 'خانواده خاموش',
            'slug' => 'off-family',
            'is_active' => true,
            'show_in_mega' => false,
            'position' => 0,
        ]);

        $payload = app(MegaMenuPayloadBuilder::class)->build(false);

        $this->assertFalse($payload['has_families']);
        $this->assertSame([], $payload['families']);
    }

    public function test_builds_family_columns_with_children_and_custom_links(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        $root = Category::query()->create([
            'product_family_id' => $family->id,
            'name' => 'چینی پردیس',
            'slug' => 'pardis',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        $child = Category::query()->create([
            'parent_id' => $root->id,
            'product_family_id' => $family->id,
            'name' => 'استک ۶ نفره',
            'slug' => 'stack-6',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        MegaMenuLink::query()->create([
            'product_family_id' => $family->id,
            'category_id' => $root->id,
            'label' => 'پیشنهاد ویژه',
            'url' => '/offers/pardis',
            'position' => 0,
            'is_active' => true,
        ]);

        $payload = app(MegaMenuPayloadBuilder::class)->build(false);

        $this->assertTrue($payload['has_families']);
        $this->assertCount(1, $payload['families']);
        $this->assertSame('سرویس چینی', $payload['families'][0]['name']);

        $column = $payload['families'][0]['columns'][0];
        $this->assertSame('چینی پردیس', $column['title']);
        $labels = array_column($column['items'], 'label');
        $this->assertContains('استک ۶ نفره', $labels);
        $this->assertContains('پیشنهاد ویژه', $labels);
    }
}
