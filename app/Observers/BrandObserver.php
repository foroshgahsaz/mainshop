<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\Cache\ShopCacheService;
use App\Services\Representative\RepresentativeCatalogCache;

class BrandObserver
{
    public function __construct(
        protected ShopCacheService $cache,
        protected RepresentativeCatalogCache $representativeCatalogCache,
    ) {}

    public function saved(Brand $brand): void
    {
        $this->cache->forgetBrand();
        $this->representativeCatalogCache->flush();
    }

    public function deleted(Brand $brand): void
    {
        $this->cache->forgetBrand();
        $this->representativeCatalogCache->flush();
    }
}
