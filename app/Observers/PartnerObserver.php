<?php

namespace App\Observers;

use App\Models\Partner;
use App\Services\Cache\ShopCacheService;

class PartnerObserver
{
    public function __construct(
        protected ShopCacheService $cache,
    ) {}

    public function saved(Partner $partner): void
    {
        $this->cache->forgetHome();
    }

    public function deleted(Partner $partner): void
    {
        $this->cache->forgetHome();
    }
}
