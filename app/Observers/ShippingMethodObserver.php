<?php

namespace App\Observers;

use App\Models\ShippingMethod;
use App\Services\Cache\ShopCacheService;

class ShippingMethodObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(ShippingMethod $shippingMethod): void
    {
        $this->cache->forgetShipping();
    }

    public function deleted(ShippingMethod $shippingMethod): void
    {
        $this->cache->forgetShipping();
    }
}
