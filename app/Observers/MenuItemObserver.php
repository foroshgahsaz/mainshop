<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Services\Cache\ShopCacheService;

class MenuItemObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(MenuItem $menuItem): void
    {
        $this->cache->forgetMenus();
    }

    public function deleted(MenuItem $menuItem): void
    {
        $this->cache->forgetMenus();
    }
}
