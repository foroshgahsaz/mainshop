<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\MegaMenuLink;
use App\Services\Shop\MegaMenuPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MegaMenuPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_when_no_roots_in_mega(): void
    {
        Category::query()->create([
            'name' => 'ریشه خاموش',
            'slug' => 'off-root',
            'is_active' => true,
            'show_in_mega' => false,
            'position' => 0,
        ]);

        $payload = app(MegaMenuPayloadBuilder::class)->build(false);

        $this->assertFalse($payload['has_families']);
        $this->assertFalse($payload['has_category_tree']);
        $this->assertSame([], $payload['families']);
    }

    public function test_builds_category_tree_columns_with_children_and_custom_links(): void
    {
        $root = Category::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-root',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        $column = Category::query()->create([
            'parent_id' => $root->id,
            'name' => 'چینی پردیس',
            'slug' => 'pardis',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        Category::query()->create([
            'parent_id' => $column->id,
            'name' => 'استک ۶ نفره',
            'slug' => 'stack-6',
            'is_active' => true,
            'show_in_mega' => true,
            'position' => 0,
        ]);

        $linkData = [
            'category_id' => $column->id,
            'label' => 'پیشنهاد ویژه',
            'url' => '/offers/pardis',
            'position' => 0,
            'is_active' => true,
        ];

        if (Schema::hasColumn('mega_menu_links', 'root_category_id')) {
            $linkData['root_category_id'] = $root->id;
        }

        MegaMenuLink::query()->create($linkData);

        $payload = app(MegaMenuPayloadBuilder::class)->build(false);

        $this->assertTrue($payload['has_category_tree']);
        $this->assertCount(1, $payload['roots']);
        $this->assertSame('سرویس چینی', $payload['roots'][0]['name']);

        $builtColumn = $payload['roots'][0]['columns'][0];
        $this->assertSame('چینی پردیس', $builtColumn['title']);
        $labels = array_column($builtColumn['items'], 'label');
        $this->assertContains('استک ۶ نفره', $labels);
        $this->assertContains('پیشنهاد ویژه', $labels);
    }
}
