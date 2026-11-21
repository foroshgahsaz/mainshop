<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\Shop\CuratedProductListingService;
use App\Support\ShopStaffPreview;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        protected CuratedProductListingService $curatedProducts,
    ) {}

    public function show(Page $page): View
    {
        $staffPreview = ShopStaffPreview::enabled();
        abort_unless($page->isVisibleOnShop($staffPreview), 404);

        if ($page->isProductGrid()) {
            $products = $this->curatedProducts->productsForIds(
                $page->curatedProductIds(),
                $staffPreview,
            );

            return view('pages.show-product-grid', compact('page', 'products'));
        }

        return view('pages.show', compact('page'));
    }
}
