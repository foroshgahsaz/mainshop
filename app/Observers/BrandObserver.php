<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\Cache\ShopCacheService;

class BrandObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(Brand $brand): void
    {
        $this->cache->forgetBrand();
    }

    public function deleted(Brand $brand): void
    {
        $this->cache->forgetBrand();
    }
}
