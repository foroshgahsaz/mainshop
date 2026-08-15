<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsShopProducts;
use App\Models\Category;
use App\Services\Cache\ShopCacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ListsShopProducts;

    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function show(Category $category, Request $request): View
    {
        abort_unless($category->is_active, 404);

        return $this->productListingView($request, $this->cache, category: $category);
    }
}
