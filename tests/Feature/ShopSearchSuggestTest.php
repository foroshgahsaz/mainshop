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
}
