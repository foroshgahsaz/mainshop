<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Services\Cache\ShopCacheService;

class ProductImageObserver
{
    public function __construct(
        protected ShopCacheService $cache
    ) {}

    public function saved(ProductImage $image): void
    {
        $this->forgetProductCache($image);
    }

    public function deleted(ProductImage $image): void
    {
        $this->forgetProductCache($image);
    }

    protected function forgetProductCache(ProductImage $image): void
    {
        $product = $image->relationLoaded('product')
            ? $image->product
            : $image->product()->select(['id', 'slug'])->first();

        if ($product) {
            $this->cache->forgetProduct($product);
        }
    }
}
