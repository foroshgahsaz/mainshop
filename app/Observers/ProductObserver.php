<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Services\Cache\ShopCacheService;
use App\Services\Representative\RepresentativeCatalogCache;

class ProductObserver
{
    public function __construct(
        protected ShopCacheService $cache,
        protected RepresentativeCatalogCache $representativeCatalogCache,
    ) {}

    public function saved(Product $product): void
    {
        $this->cache->forgetProduct($product);
        $this->representativeCatalogCache->flush();
    }

    public function deleted(Product $product): void
    {
        $this->cache->forgetProduct($product);
        $this->representativeCatalogCache->flush();
    }
}
