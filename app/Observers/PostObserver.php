<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\Cache\ShopCacheService;

class PostObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(Post $post): void
    {
        $this->cache->forgetBlog();
        $this->cache->forgetHome();
    }

    public function deleted(Post $post): void
    {
        $this->cache->forgetBlog();
        $this->cache->forgetHome();
    }
}
