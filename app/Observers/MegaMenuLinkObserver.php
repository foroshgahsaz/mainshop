<?php

namespace App\Observers;

use App\Models\MegaMenuLink;
use App\Services\Cache\ShopCacheService;

class MegaMenuLinkObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(MegaMenuLink $megaMenuLink): void
    {
        $this->cache->forgetCategory();
    }

    public function deleted(MegaMenuLink $megaMenuLink): void
    {
        $this->cache->forgetCategory();
    }
}
