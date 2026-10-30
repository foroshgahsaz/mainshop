<?php

namespace App\Observers;

use App\Models\CreditPlan;
use App\Services\Cache\ShopCacheService;

class CreditPlanObserver
{
    public function __construct(
        protected ShopCacheService $cache,
    ) {}

    public function saved(CreditPlan $creditPlan): void
    {
        $this->cache->forgetHome();
    }

    public function deleted(CreditPlan $creditPlan): void
    {
        $this->cache->forgetHome();
    }
}
