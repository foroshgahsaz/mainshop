<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Services\Cache\ShopCacheService;
use Illuminate\View\View;

class CreditPlanIndexController extends Controller
{
    public function __invoke(ShopCacheService $cache): View
    {
        $items = $cache->plansIndexCards();

        abort_if($items === [], 404);

        return view('shop.plans.index', [
            'plans' => $items,
            'planCount' => count($items),
        ]);
    }
}
