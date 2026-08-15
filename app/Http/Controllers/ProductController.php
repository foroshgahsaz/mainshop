<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsShopProducts;
use App\Models\Product;
use App\Services\Cache\ShopCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProductController extends Controller
{
    use ListsShopProducts;

    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function index(Request $request): View
    {
        return $this->productListingView($request, $this->cache);
    }

    public function show(Product $product): View
    {
        $cached = $this->cache->productBySlug($product->slug);

        abort_unless($cached && $cached->is_active, 404);

        $product = $cached;

        Cache::remember(
            'shop:product:views:'.$product->id.':'.request()->ip(),
            3600,
            function () use ($product) {
                Product::whereKey($product->id)->increment('views');

                return true;
            }
        );

        $relatedProducts = $this->cache->relatedProducts($product->category_id, $product->id);

        return view('shop.products.show', compact('product', 'relatedProducts'));
    }
}
