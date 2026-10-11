<?php

namespace App\Observers;

use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use App\Services\Representative\RepresentativeCatalogCache;

class RepresentativeCatalogObserver
{
    public function __construct(
        protected RepresentativeCatalogCache $catalogCache,
        protected ShopCacheService $shopCache,
    ) {}

    public function saved(object $model): void
    {
        $this->catalogCache->flush();

        if ($model instanceof ProductFamily) {
            $this->shopCache->forgetHome();
        }
    }

    public function deleted(object $model): void
    {
        $this->catalogCache->flush();

        if ($model instanceof ProductFamily) {
            $this->shopCache->forgetHome();
        }
    }
}
