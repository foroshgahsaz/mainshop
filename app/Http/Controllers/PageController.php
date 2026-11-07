<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\ShopStaffPreview;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->isVisibleOnShop(ShopStaffPreview::enabled()), 404);

        return view('pages.show', compact('page'));
    }
}
