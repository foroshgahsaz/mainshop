<?php

namespace App\Http\Controllers;

use App\Services\Cache\ShopCacheService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function index(): View
    {
        $data = $this->cache->homePayload();

        return view('shop.home', $data);
    }
}
