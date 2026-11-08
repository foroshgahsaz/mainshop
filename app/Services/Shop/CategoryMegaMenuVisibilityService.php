<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use Illuminate\Support\Facades\Schema;

/**
 * روشن کردن show_in_mega روی ریشه‌های map‌شده و زیردسته‌های فعال (ساختار ستون/آیتم مگا منو).
 */
final class CategoryMegaMenuVisibilityService
{
    public function __construct(
        protected CatalogFamilyCategoryMapper $mapper,
        protected ShopCacheService $cache,
    ) {}

    /**
     * @return array{roots: list<int>, categories_enabled: int}
     */
    public function syncFromMappedFamilies(bool $dryRun = false): array
    {
        if (! Schema::hasColumn('categories', 'show_in_mega')) {
            return ['roots' => [], 'categories_enabled' => 0];
        }

        $rootIds = $this->resolveMegaRootIds();
        if ($rootIds === []) {
            return ['roots' => [], 'categories_enabled' => 0];
        }

        $enabled = 0;

        foreach ($rootIds as $rootId) {
            $enabled += $this->enableMegaTree((int) $rootId, $dryRun);
        }

        if (! $dryRun && $enabled > 0) {
            $this->cache->forgetCategory();
        }

        return [
            'roots' => $rootIds,
            'categories_enabled' => $enabled,
        ];
    }

    /** @return list<int> */
    protected function resolveMegaRootIds(): array
    {
        $ids = collect();

        if (Schema::hasTable('product_families')) {
            ProductFamily::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get(['id', 'show_in_mega'])
                ->each(function (ProductFamily $family) use ($ids): void {
                    $rootId = $this->mapper->rootCategoryIdForFamily($family);
                    if ($rootId === null) {
                        return;
                    }

                    if ($family->show_in_mega) {
                        $ids->push($rootId);
                    }
                });
        }

        Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->where('show_in_mega', true)
            ->pluck('id')
            ->each(fn ($id) => $ids->push((int) $id));

        if ($ids->isEmpty() && Schema::hasTable('product_families')) {
            ProductFamily::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->pluck('id')
                ->each(function (int $familyId) use ($ids): void {
                    $rootId = $this->mapper->rootCategoryIdForFamilyId($familyId);
                    if ($rootId !== null) {
                        $ids->push($rootId);
                    }
                });
        }

        return $ids->unique()->filter()->values()->all();
    }

    protected function enableMegaTree(int $rootId, bool $dryRun): int
    {
        $root = Category::query()
            ->where('is_active', true)
            ->whereKey($rootId)
            ->first();

        if ($root === null) {
            return 0;
        }

        $enabled = 0;
        $stack = [$root];

        while ($stack !== []) {
            /** @var Category $category */
            $category = array_pop($stack);

            if (! $category->show_in_mega) {
                $enabled++;
                if (! $dryRun) {
                    Category::withoutEvents(function () use ($category): void {
                        $category->show_in_mega = true;
                        $category->save();
                    });
                }
            }

            $children = Category::query()
                ->where('parent_id', $category->id)
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            foreach ($children as $child) {
                $stack[] = $child;
            }
        }

        return $enabled;
    }
}
