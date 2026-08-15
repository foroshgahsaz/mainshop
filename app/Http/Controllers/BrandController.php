<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsShopProducts;
use App\Models\Brand;
use App\Services\Cache\ShopCacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    use ListsShopProducts;

    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function show(Brand $brand, Request $request): View
    {
        abort_unless($brand->is_active, 404);

        return $this->productListingView($request, $this->cache, brand: $brand);
    }
}
