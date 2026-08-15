<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $urls = [];

        $static = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('blog.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];

        foreach ($static as $item) {
            $urls[] = $this->urlEntry($item['loc'], null, $item['changefreq'], $item['priority']);
        }

        Page::where('is_active', true)->get(['slug', 'updated_at'])->each(function (Page $page) use (&$urls) {
            $urls[] = $this->urlEntry(route('pages.show', $page), $page->updated_at, 'monthly', '0.5');
        });

        Product::active()->get(['slug', 'updated_at'])->each(function (Product $product) use (&$urls) {
            $urls[] = $this->urlEntry(route('products.show', $product), $product->updated_at, 'weekly', '0.8');
        });

        Category::where('is_active', true)->get(['slug', 'updated_at'])->each(function (Category $category) use (&$urls) {
            $urls[] = $this->urlEntry(route('categories.show', $category), $category->updated_at, 'weekly', '0.7');
        });

        Brand::where('is_active', true)->get(['slug', 'updated_at'])->each(function (Brand $brand) use (&$urls) {
            $urls[] = $this->urlEntry(route('brands.show', $brand), $brand->updated_at, 'weekly', '0.6');
        });

        Post::where('is_active', true)->get(['slug', 'updated_at'])->each(function (Post $post) use (&$urls) {
            $urls[] = $this->urlEntry(route('blog.show', $post), $post->updated_at, 'weekly', '0.6');
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', $urls)
            .'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    protected function urlEntry(string $loc, $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5'): string
    {
        $entry = '<url><loc>'.e($loc).'</loc>';

        if ($lastmod) {
            $entry .= '<lastmod>'.e($lastmod->toAtomString()).'</lastmod>';
        }

        $entry .= '<changefreq>'.e($changefreq).'</changefreq>';
        $entry .= '<priority>'.e($priority).'</priority></url>';

        return $entry;
    }
}
