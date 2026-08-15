<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cache\ShopCacheService;

class CategoryObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(Category $category): void
    {
        $this->cache->forgetCategory();
    }

    public function deleted(Category $category): void
    {
        $this->cache->forgetCategory();
    }
}
