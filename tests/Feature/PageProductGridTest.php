<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageProductGridTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_grid_page_shows_selected_products_in_order(): void
    {
        $category = Category::query()->create([
            'name' => 'Camp',
            'slug' => 'camp-'.uniqid(),
            'is_active' => true,
        ]);

        $first = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'اول کمپین',
            'slug' => 'first-'.uniqid(),
            'price' => 1_000_000,
            'stock' => 3,
            'is_active' => true,
        ]);
        $second = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'دوم کمپین',
            'slug' => 'second-'.uniqid(),
            'price' => 2_000_000,
            'stock' => 3,
            'is_active' => true,
        ]);

        $page = Page::query()->create([
            'title' => 'بلک فرایدی',
            'slug' => 'test',
            'layout' => Page::LAYOUT_PRODUCT_GRID,
            'content' => null,
            'product_ids' => [$second->id, $first->id],
            'is_active' => true,
        ]);

        $response = $this->get(route('pages.show', $page));

        $response->assertOk();
        $response->assertSeeInOrder(['دوم کمپین', 'اول کمپین'], false);
        $response->assertSee('بلک فرایدی', false);
    }

    public function test_content_page_still_renders_cms_view(): void
    {
        $page = Page::query()->create([
            'title' => 'قوانین',
            'slug' => 'terms-sample',
            'layout' => Page::LAYOUT_CONTENT,
            'content' => '<p>متن قوانین</p>',
            'is_active' => true,
        ]);

        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('متن قوانین', false);
    }
}