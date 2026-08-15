<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Services\Cache\ShopCacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait ListsShopProducts
{
    protected function productListingView(
        Request $request,
        ShopCacheService $cache,
        ?Category $category = null,
        ?Brand $brand = null,
    ): View {
        $filters = [
            'search' => $request->search,
            'category_id' => $category?->id ?? $request->category_id,
            'brand_id' => $brand?->id ?? $request->brand_id,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'sort' => $request->get('sort', 'created_at'),
            'direction' => $request->get('direction', 'desc'),
            'page' => $request->get('page', 1),
        ];

        $products = $cache->productListing($filters);
        $categories = $cache->categories();
        $brands = $cache->brands();
        $priceBounds = $cache->productPriceBounds();

        $pageTitle = match (true) {
            $request->filled('search') => 'نتایج جستجو',
            $category !== null => 'محصولات '.$category->name,
            $brand !== null => 'محصولات '.$brand->name,
            default => 'لیست کالاها',
        };

        return view('shop.products.index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'category' => $category,
            'brand' => $brand,
            'pageTitle' => $pageTitle,
            'filters' => $filters,
            'priceBounds' => $priceBounds,
            'searchQuery' => $request->search,
        ]);
    }
}
