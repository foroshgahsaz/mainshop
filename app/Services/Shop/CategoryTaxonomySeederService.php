<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\MegaMenuLink;
use App\Models\Product;
use App\Services\Cache\ShopCacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class CategoryTaxonomySeederService
{
    public function __construct(
        protected ShopCacheService $cache,
    ) {}

    /**
     * @return array{
     *     created: int,
     *     deleted: int,
     *     products_remapped: int,
     *     mega_links_removed: int,
     *     roots: list<int>
     * }
     */
    public function run(bool $dryRun = false): array
    {
        $productNamesByProductId = $this->snapshotProductCategoryNames();
        $megaLinkCount = Schema::hasTable('mega_menu_links')
            ? MegaMenuLink::query()->count()
            : 0;

        if ($dryRun) {
            $nodeCount = $this->countNodes(CategoryTaxonomyBlueprint::tree());
            $oldCount = Category::query()->count();

            return [
                'created' => $nodeCount,
                'deleted' => $oldCount,
                'products_remapped' => count($productNamesByProductId),
                'mega_links_removed' => $megaLinkCount,
                'roots' => [],
            ];
        }

        $result = Category::withoutEvents(function () use ($productNamesByProductId, $megaLinkCount): array {
            return DB::transaction(function () use ($productNamesByProductId, $megaLinkCount): array {
            if (Schema::hasTable('mega_menu_links')) {
                MegaMenuLink::query()->delete();
            }

            $deleted = $this->deleteAllCategories();

            $createdIds = [];
            $positionRoot = 0;
            $nameToId = [];

            foreach (CategoryTaxonomyBlueprint::tree() as $node) {
                $this->createNode(
                    $node,
                    parentId: null,
                    depth: 0,
                    position: $positionRoot++,
                    createdIds: $createdIds,
                    nameToId: $nameToId,
                );
            }

            $remapped = $this->remapProducts($productNamesByProductId, $nameToId);

            return [
                'created' => count($createdIds),
                'deleted' => $deleted,
                'products_remapped' => $remapped,
                'mega_links_removed' => $megaLinkCount,
                'roots' => Category::query()->whereNull('parent_id')->orderBy('position')->pluck('id')->all(),
            ];
            });
        });
        $this->cache->forgetCategory();
        $this->cache->forgetHome();

        return $result;
    }

    /** @return array<int, string> */
    protected function snapshotProductCategoryNames(): array
    {
        $map = [];

        Product::query()
            ->whereNotNull('category_id')
            ->with('category:id,name')
            ->select(['id', 'category_id'])
            ->chunkById(500, function ($products) use (&$map): void {
                foreach ($products as $product) {
                    $name = trim((string) ($product->category?->name ?? ''));
                    if ($name !== '') {
                        $map[(int) $product->id] = $name;
                    }
                }
            });

        return $map;
    }

    protected function deleteAllCategories(): int
    {
        $total = Category::query()->count();
        if ($total === 0) {
            return 0;
        }

        $guard = 0;
        while (Category::query()->exists() && $guard < 50_000) {
            $deleted = Category::query()
                ->whereDoesntHave('children')
                ->limit(500)
                ->delete();
            if ($deleted === 0) {
                Category::query()->limit(500)->delete();
            }
            $guard++;
        }

        return $total;
    }

    /**
     * @param  array{name: string, children?: list<array<string, mixed>>}  $node
     * @param  list<int>  $createdIds
     * @param  array<string, int>  $nameToId normalized name => id
     */
    protected function createNode(
        array $node,
        ?int $parentId,
        int $depth,
        int $position,
        array &$createdIds,
        array &$nameToId,
    ): void {
        $name = trim((string) $node['name']);
        $slug = $this->uniqueSlug($name);

        $category = Category::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'admin_shop_preview' => false,
            'position' => $position,
            'show_in_mega' => $depth <= 2,
        ]);

        $createdIds[] = (int) $category->id;
        $nameToId[$this->normalizeName($name)] = (int) $category->id;

        $children = $node['children'] ?? [];
        $childPosition = 0;
        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }
            $this->createNode(
                $child,
                parentId: (int) $category->id,
                depth: $depth + 1,
                position: $childPosition++,
                createdIds: $createdIds,
                nameToId: $nameToId,
            );
        }
    }

    /**
     * @param  array<int, string>  $productNamesByProductId
     * @param  array<string, int>  $nameToId
     */
    protected function remapProducts(array $productNamesByProductId, array $nameToId): int
    {
        $remapped = 0;

        foreach ($productNamesByProductId as $productId => $oldName) {
            $newId = $this->resolveCategoryIdForName($oldName, $nameToId);
            if ($newId === null) {
                continue;
            }

            Product::query()->whereKey($productId)->update(['category_id' => $newId]);
            $remapped++;
        }

        return $remapped;
    }

    /** @param  array<string, int>  $nameToId */
    protected function resolveCategoryIdForName(string $name, array $nameToId): ?int
    {
        $key = $this->normalizeName($name);
        if (isset($nameToId[$key])) {
            return $nameToId[$key];
        }

        foreach ($nameToId as $normalized => $id) {
            if (str_contains($normalized, $key) || str_contains($key, $normalized)) {
                return $id;
            }
        }

        return null;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-', 'fa');
        if ($base === '') {
            $base = 'daste-'.substr(md5($name), 0, 10);
        }

        $slug = $base;
        $suffix = 2;
        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    protected function normalizeName(string $name): string
    {
        $name = str_replace(["\u{200C}", '‌', ' ', '،', ',', '؛', ';'], '', trim($name));

        return mb_strtolower($name);
    }

    /** @param  list<array{name: string, children?: list<mixed>}>  $nodes */
    protected function countNodes(array $nodes): int
    {
        $count = 0;
        foreach ($nodes as $node) {
            $count++;
            $children = $node['children'] ?? [];
            if (is_array($children) && $children !== []) {
                $count += $this->countNodes($children);
            }
        }

        return $count;
    }
}
