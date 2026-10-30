<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Services\Shop\HomepageCreditPlanService;
use Illuminate\View\View;

class CreditPlanIndexController extends Controller
{
    public function __invoke(HomepageCreditPlanService $plans): View
    {
        $items = $plans->allActiveCards();

        abort_if($items === [], 404);

        return view('shop.plans.index', [
            'plans' => $items,
        ]);
    }
}
