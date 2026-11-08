<?php

namespace App\Services\Shop;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

final class CategoryTreeService
{
    /**
     * @return list<int>
     */
    public function descendantIdsIncludingSelf(int $categoryId): array
    {
        $cacheKey = 'shop:category:descendants:'.$categoryId;

        return Cache::remember($cacheKey, 300, function () use ($categoryId): array {
            $ids = [];
            $frontier = [$categoryId];

            while ($frontier !== []) {
                $next = [];

                foreach ($frontier as $parentId) {
                    $ids[] = $parentId;

                    $children = Category::query()
                        ->where('parent_id', $parentId)
                        ->where('is_active', true)
                        ->pluck('id')
                        ->all();

                    foreach ($children as $childId) {
                        $next[] = $childId;
                    }
                }

                $frontier = $next;
            }

            return array_values(array_unique($ids));
        });
    }

    public function forgetDescendantsCache(int $categoryId): void
    {
        Cache::forget('shop:category:descendants:'.$categoryId);
    }
}
