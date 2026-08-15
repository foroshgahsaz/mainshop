<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Services\Cache\ShopCacheService;

class ProductObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(Product $product): void
    {
        $this->cache->forgetProduct($product);
    }

    public function deleted(Product $product): void
    {
        $this->cache->forgetProduct($product);
    }
}
