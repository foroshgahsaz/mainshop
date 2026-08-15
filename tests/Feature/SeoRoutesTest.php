<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_product_and_listing_routes_work(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Item',
            'slug' => 'item',
            'price' => 100000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->get('/products')->assertOk();
        $this->get('/products/'.$product->slug)->assertOk();
        $this->get('/categories/'.$category->slug)->assertOk();
        $this->get('/brands/'.$brand->slug)->assertOk();
        $this->get('/product/'.$product->slug)->assertRedirect('/products/'.$product->slug);
    }

    public function test_product_listing_filters_by_price_and_brand(): void
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat', 'is_active' => true]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);

        Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Cheap',
            'slug' => 'cheap',
            'price' => 50000,
            'stock' => 5,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Expensive',
            'slug' => 'expensive',
            'price' => 500000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->get('/products?brand_id='.$brand->id.'&max_price=100000')
            ->assertOk()
            ->assertSee('Cheap')
            ->assertDontSee('Expensive');
    }

    public function test_blog_and_author_routes_work(): void
    {
        $author = User::factory()->create([
            'name' => 'Writer',
            'slug' => 'writer',
            'is_author' => true,
        ]);

        $post = Post::create([
            'user_id' => $author->id,
            'title' => 'Hello',
            'slug' => 'hello',
            'content' => 'Body',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $this->get('/blog')->assertOk();
        $this->get('/blog/'.$post->slug)->assertOk()->assertSee('Hello');
        $this->get('/authors/'.$author->slug)->assertOk()->assertSee('Writer');
    }
}
