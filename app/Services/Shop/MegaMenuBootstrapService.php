<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * یک‌بار (یا تکرار امن): خانواده و دسته‌های فعلی را برای مگا منوی خانواده‌محور آماده می‌کند.
 */
final class MegaMenuBootstrapService
{
    public function schemaReady(): bool
    {
        return Schema::hasColumn('product_families', 'show_in_mega')
            && Schema::hasColumn('categories', 'product_family_id')
            && Schema::hasTable('mega_menu_links');
    }

    /**
     * @return array{
     *     families_enabled: int,
     *     roots_updated: int,
     *     descendants_updated: int,
     *     roots: list<array{name: string, family: string}>
     * }
     */
    public function run(
        bool $dryRun = false,
        bool $includeAllRoots = false,
        ?int $forceFamilyId = null,
    ): array {
        if (! $this->schemaReady()) {
            throw new \RuntimeException('اسکیما مگا منو آماده نیست. ابتدا: php artisan migrate --force');
        }

        $roots = $this->rootCategories($includeAllRoots);
        if ($roots->isEmpty()) {
            throw new \RuntimeException('هیچ دستهٔ ریشهٔ فعالی برای مگا منو پیدا نشد.');
        }

        $families = ProductFamily::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        if ($families->isEmpty()) {
            throw new \RuntimeException('هیچ خانوادهٔ محصول فعالی وجود ندارد. ابتدا در ادمین خانواده بسازید.');
        }

        $fallbackFamilyId = $forceFamilyId ?? $this->defaultFamilyId($families);

        $familyIdsInMega = [];
        $rootsPlan = [];
        $rootsUpdated = 0;
        $descendantsUpdated = 0;

        foreach ($roots as $root) {
            $familyId = $forceFamilyId ?? $this->resolveFamilyIdForRoot($root, $families, $fallbackFamilyId);
            $family = $families->firstWhere('id', $familyId) ?? $families->first();

            $familyIdsInMega[$family->id] = true;

            $rootsPlan[] = [
                'name' => $root->name,
                'family' => $family->name,
            ];

            if ($dryRun) {
                $descendantsUpdated += $this->countDescendants($root);

                continue;
            }

            DB::transaction(function () use ($root, $family, &$rootsUpdated, &$descendantsUpdated): void {
                Category::withoutEvents(function () use ($root, $family, &$rootsUpdated, &$descendantsUpdated): void {
                    $root->product_family_id = $family->id;
                    $root->show_in_mega = true;
                    $root->save();
                    $rootsUpdated++;

                    $descendantsUpdated += $this->applyFamilyToDescendants($root, $family->id);
                });
            });
        }

        $familiesEnabled = 0;
        if (! $dryRun) {
            ProductFamily::withoutEvents(function () use ($familyIdsInMega, &$familiesEnabled): void {
                ProductFamily::query()
                    ->whereIn('id', array_keys($familyIdsInMega))
                    ->each(function (ProductFamily $family) use (&$familiesEnabled): void {
                        if (! $family->show_in_mega) {
                            $family->show_in_mega = true;
                            $family->save();
                            $familiesEnabled++;
                        }
                    });
            });

            app(ShopCacheService::class)->forgetCategory();
        } else {
            $familiesEnabled = count($familyIdsInMega);
        }

        return [
            'families_enabled' => $familiesEnabled,
            'roots_updated' => $dryRun ? $roots->count() : $rootsUpdated,
            'descendants_updated' => $descendantsUpdated,
            'roots' => $rootsPlan,
        ];
    }

  /**
   * @return Collection<int, Category>
   */
    private function rootCategories(bool $includeAllRoots): Collection
    {
        $query = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id');

        if (! $includeAllRoots) {
            $withMega = (clone $query)->where('show_in_mega', true)->get();
            if ($withMega->isNotEmpty()) {
                return $withMega;
            }
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, ProductFamily>  $families
     */
    private function defaultFamilyId(Collection $families): int
    {
        $chinese = $families->first(fn (ProductFamily $f) => $f->slug === 'chinese-service'
            || str_contains($f->name, 'چینی'));

        return ($chinese ?? $families->first())->id;
    }

    /**
     * @param  Collection<int, ProductFamily>  $families
     */
    private function resolveFamilyIdForRoot(Category $root, Collection $families, int $fallbackFamilyId): int
    {
        if ($root->product_family_id && $families->contains('id', $root->product_family_id)) {
            return (int) $root->product_family_id;
        }

        $categoryIds = $this->collectCategoryTreeIds($root);

        $familyId = Product::query()
            ->whereIn('category_id', $categoryIds)
            ->whereNotNull('product_family_id')
            ->where('is_active', true)
            ->groupBy('product_family_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('product_family_id');

        if ($familyId && $families->contains('id', (int) $familyId)) {
            return (int) $familyId;
        }

        return $fallbackFamilyId;
    }

    /** @return list<int> */
    private function collectCategoryTreeIds(Category $root): array
    {
        $ids = [$root->id];
        $this->gatherChildIds($root->id, $ids);

        return $ids;
    }

    /** @param  list<int>  $ids */
    private function gatherChildIds(int $parentId, array &$ids): void
    {
        $children = Category::query()
            ->where('parent_id', $parentId)
            ->pluck('id');

        foreach ($children as $childId) {
            $ids[] = (int) $childId;
            $this->gatherChildIds((int) $childId, $ids);
        }
    }

    private function applyFamilyToDescendants(Category $root, int $familyId): int
    {
        $updated = 0;
        $stack = [$root->id];

        while ($stack !== []) {
            $parentId = array_pop($stack);
            $children = Category::query()
                ->where('parent_id', $parentId)
                ->where('is_active', true)
                ->get();

            foreach ($children as $child) {
                $child->product_family_id = $familyId;
                if (! $child->show_in_mega) {
                    $child->show_in_mega = true;
                }
                $child->save();
                $updated++;
                $stack[] = $child->id;
            }
        }

        return $updated;
    }

    private function countDescendants(Category $root): int
    {
        $ids = $this->collectCategoryTreeIds($root);

        return max(0, count($ids) - 1);
    }
}
