<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsShopProducts;
use App\Models\Category;
use App\Services\Cache\ShopCacheService;
use App\Services\Shop\CategoryPathResolver;
use App\Support\ShopStaffPreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ListsShopProducts;

    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function show(Category $category, Request $request): View|RedirectResponse
    {
        abort_unless($category->isVisibleOnShop(ShopStaffPreview::enabled()), 404);

        $resolver = app(CategoryPathResolver::class);
        $canonical = $resolver->pathFor($category);
        $requestedPath = implode('/', array_slice($request->segments(), 1));

        if ($requestedPath !== $canonical) {
            return redirect()->to(url('/categories/'.$canonical), 301);
        }

        return $this->productListingView($request, $this->cache, category: $category);
    }
}
