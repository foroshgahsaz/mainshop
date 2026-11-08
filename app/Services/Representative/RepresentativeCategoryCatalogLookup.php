<?php

namespace App\Services\Representative;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Shop\CategoryTreeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class RepresentativeCategoryCatalogLookup
{
    public function __construct(
        protected RepresentativeCatalogCache $catalogCache,
        protected CategoryTreeService $categoryTree,
    ) {}

    /** @return Collection<int, Category> */
    public function browseCategories(?int $parentId): Collection
    {
        $key = 'rep:catalog:categories:'.($parentId ?? 'root');

        return $this->catalogCache->remember(
            $key,
            $this->cacheTtl(),
            function () use ($parentId): Collection {
                $query = Category::query()
                    ->where('is_active', true)
                    ->orderBy('position')
                    ->orderBy('name');

                if ($parentId === null) {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $parentId);
                }

                return $query
                    ->get(['id', 'name', 'parent_id'])
                    ->filter(fn (Category $category): bool => $this->categoryHasBrowsableCatalog($category->id))
                    ->values();
            }
        );
    }

    public function shouldDrillIntoCategory(int $categoryId): bool
    {
        $children = Category::query()
            ->where('parent_id', $categoryId)
            ->where('is_active', true)
            ->pluck('id');

        foreach ($children as $childId) {
            if ($this->categoryHasBrowsableCatalog((int) $childId)) {
                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, Brand> */
    public function brandsForCategory(int $categoryId): Collection
    {
        return $this->catalogCache->remember(
            'rep:catalog:category-brands:'.$categoryId,
            $this->cacheTtl(),
            function () use ($categoryId): Collection {
                $categoryIds = $this->categoryTree->descendantIdsIncludingSelf($categoryId);

                $brandIds = Product::query()
                    ->whereIn('category_id', $categoryIds)
                    ->tap(fn (Builder $q) => $this->baseProductQuery($q))
                    ->whereNotNull('brand_id')
                    ->distinct()
                    ->pluck('brand_id');

                return Brand::query()
                    ->whereIn('id', $brandIds)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        );
    }

    public function products(
        int $categoryId,
        ?int $brandId,
        string $search = '',
        int $page = 1,
    ): LengthAwarePaginator {
        $perPage = max(5, (int) config('shop.representative.products_per_page', 15));
        $categoryIds = $this->categoryTree->descendantIdsIncludingSelf($categoryId);

        return Product::query()
            ->whereIn('category_id', $categoryIds)
            ->when(
                $brandId !== null,
                fn (Builder $q) => $q->where('brand_id', $brandId),
            )
            ->tap(fn (Builder $q) => $this->baseProductQuery($q))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->with(['images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('position')])
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'sku', 'price', 'sale_price', 'stock', 'brand_id'], 'page', $page);
    }

    public function categoryHasBrowsableCatalog(int $categoryId): bool
    {
        if ($this->shouldDrillIntoCategory($categoryId)) {
            return true;
        }

        $categoryIds = $this->categoryTree->descendantIdsIncludingSelf($categoryId);

        return Product::query()
            ->whereIn('category_id', $categoryIds)
            ->tap(fn (Builder $q) => $this->baseProductQuery($q))
            ->exists();
    }

    private function baseProductQuery(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    private function cacheTtl(): int
    {
        return max(60, (int) config('shop.representative.catalog_cache_seconds', 300));
    }
}
