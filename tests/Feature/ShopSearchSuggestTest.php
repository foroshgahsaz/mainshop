<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSearchSuggestTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggest_requires_at_least_three_characters(): void
    {
        $this->getJson(route('shop.search.suggest', ['q' => 'ab']))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('items', []);
    }

    public function test_suggest_returns_matching_products(): void
    {
        $category = Category::query()->create([
            'name' => 'پوشاک',
            'slug' => 'clothing',
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'کفش ورزشی مخصوص دویدن',
            'slug' => 'running-shoes',
            'price' => 100000,
            'stock' => 5,
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'پیراهن رسمی',
            'slug' => 'formal-shirt',
            'price' => 200000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->getJson(route('shop.search.suggest', ['q' => 'کفش']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.name', 'کفش ورزشی مخصوص دویدن');
    }

    public function test_suggest_limits_items_to_five_but_reports_total(): void
    {
        $category = Category::query()->create([
            'name' => 'ظروف',
            'slug' => 'dishes',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 7; $i++) {
            Product::query()->create([
                'category_id' => $category->id,
                'name' => "کاسه سرامیک شماره {$i}",
                'slug' => "bowl-{$i}",
                'price' => 50000 + $i,
                'stock' => 3,
                'is_active' => true,
            ]);
        }

        $response = $this->getJson(route('shop.search.suggest', ['q' => 'کاسه']));

        $response->assertOk()
            ->assertJsonPath('total', 7)
            ->assertJsonCount(5, 'items')
            ->assertJsonStructure(['all_results_url']);
    }
}
