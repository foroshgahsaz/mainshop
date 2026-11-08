<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\ProductFamily;
use App\Services\Settings\HomepageSettingsService;
use App\Support\ShopFormatter;
use Illuminate\Support\Facades\Schema;

class HomepageHeroHubService
{
    public function __construct(
        protected HomepageSettingsService $homepageSettings,
        protected CategoryHomepageHeroService $categoryHero,
        protected CatalogFamilyCategoryMapper $familyCategoryMapper,
    ) {}

    /**
     * @return array{
     *     sidebar_title: string,
     *     sidebar_links: list<array{label: string, url: string, image: ?string}>,
     *     quick_families: list<array{id: int, name: string, image: ?string, url: string}>
     * }
     */
    public function payload(): array
    {
        $sidebarTitle = $this->homepageSettings->heroSidebarTitle();
        $sidebarLinks = $this->buildSidebarLinksForDisplay();

        return [
            'sidebar_title' => $sidebarTitle !== '' ? $sidebarTitle : 'سرویس چینی',
            'sidebar_links' => $sidebarLinks,
            'quick_families' => $this->resolveQuickTiles(),
        ];
    }

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function buildSidebarLinksForDisplay(): array
    {
        $fromSettings = $this->homepageSettings->heroSidebarLinks();
        if ($fromSettings !== []) {
            return $fromSettings;
        }

        $root = $this->resolvePrimaryRootCategory();
        if ($root !== null) {
            $links = $this->categoryHero->sidebarLinksForRootCategory($root);

            return $this->rewriteLegacyFamilyUrls($links);
        }

        return $this->legacyFamilySidebarFallback();
    }

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function buildSidebarLinksForStorage(): array
    {
        $root = $this->resolvePrimaryRootCategory();
        if ($root === null) {
            return [];
        }

        return $this->categoryHero->sidebarLinksForRootCategory($root, resolveImageUrls: false);
    }

    protected function resolvePrimaryRootCategory(): ?Category
    {
        $configuredId = $this->homepageSettings->heroSidebarCategoryId();
        if ($configuredId !== null) {
            $configured = Category::query()
                ->where('is_active', true)
                ->whereKey($configuredId)
                ->first();

            if ($configured !== null) {
                return $configured;
            }
        }

        $legacyFamilyId = $this->homepageSettings->heroSidebarFamilyId();
        if ($legacyFamilyId !== null) {
            $mapped = $this->familyCategoryMapper->rootCategoryIdForFamilyId($legacyFamilyId);
            if ($mapped !== null) {
                return Category::query()->where('is_active', true)->whereKey($mapped)->first();
            }
        }

        foreach ($this->defaultRootCategoryCandidates() as $candidateId) {
            $category = Category::query()
                ->where('is_active', true)
                ->whereKey($candidateId)
                ->first();

            if ($category !== null) {
                return $category;
            }
        }

        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('slug', 'سرویس-چینی')
                    ->orWhere('slug', 'chinese-service')
                    ->orWhere('name', 'سرویس چینی');
            })
            ->orderBy('position')
            ->first();
    }

    /** @return list<int> */
    protected function defaultRootCategoryCandidates(): array
    {
        $candidates = collect();

        if (Schema::hasTable('product_families')) {
            ProductFamily::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->where('slug', 'chinese-service')
                        ->orWhere('name', 'سرویس چینی');
                })
                ->pluck('id')
                ->each(function (int $familyId) use ($candidates): void {
                    $mapped = $this->familyCategoryMapper->rootCategoryIdForFamilyId($familyId);
                    if ($mapped !== null) {
                        $candidates->push($mapped);
                    }
                });
        }

        return $candidates->unique()->filter()->values()->all();
    }

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    protected function legacyFamilySidebarFallback(): array
    {
        if (! Schema::hasTable('product_families')) {
            return [];
        }

        $family = null;
        $familyId = $this->homepageSettings->heroSidebarFamilyId();
        if ($familyId !== null) {
            $family = ProductFamily::query()->where('is_active', true)->whereKey($familyId)->first();
        }

        $family ??= ProductFamily::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('slug', 'chinese-service')
                    ->orWhere('name', 'سرویس چینی');
            })
            ->orderBy('position')
            ->first();

        if ($family === null) {
            return [];
        }

        return $this->rewriteLegacyFamilyUrls(
            $this->categoryHero->legacyFamilySidebarLinks($family),
        );
    }

    /**
     * @param  list<array{label: string, url: string, image: ?string}>  $links
     * @return list<array{label: string, url: string, image: ?string}>
     */
    protected function rewriteLegacyFamilyUrls(array $links): array
    {
        return array_map(function (array $link): array {
            $url = (string) ($link['url'] ?? '');
            if (preg_match('/product_family_id=(\d+)/', $url, $matches) === 1) {
                $familyId = (int) $matches[1];
                $categoryId = $this->familyCategoryMapper->rootCategoryIdForFamilyId($familyId);
                if ($categoryId !== null) {
                    $category = Category::query()->whereKey($categoryId)->first();
                    if ($category !== null) {
                        $link['url'] = route('categories.show', $category);
                    } else {
                        $link['url'] = route('products.index', ['category_id' => $categoryId]);
                    }
                }
            }

            return $link;
        }, $links);
    }

    /**
     * @return list<array{id: int, name: string, image: ?string, url: string}>
     */
    protected function resolveQuickTiles(): array
    {
        $ids = collect($this->homepageSettings->heroQuickCategoryIds());

        if ($ids->isEmpty()) {
            $ids = collect($this->homepageSettings->heroQuickFamilyIds())
                ->map(fn (int $familyId): ?int => $this->familyCategoryMapper->rootCategoryIdForFamilyId($familyId))
                ->filter();
        }

        if ($ids->isEmpty()) {
            $ids = collect($this->defaultQuickCategoryIds());
        }

        $ids = $ids->unique()->take(3);

        if ($ids->isEmpty()) {
            return [];
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $out = [];
        foreach ($ids->all() as $id) {
            $category = $categories->get($id);
            if ($category === null) {
                continue;
            }

            $imagePath = trim((string) ($category->image ?? ''));

            $out[] = [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'image' => $imagePath !== '' ? $imagePath : null,
                'url' => route('categories.show', $category),
            ];
        }

        return $out;
    }

    /** @return list<int> */
    protected function defaultQuickCategoryIds(): array
    {
        $slugs = ['pars-opal', 'kitchenware', 'crystal-service', 'لوازم-آشپزخانه', 'سرویس-بلور'];
        $names = ['پارس اپال', 'پارس اوپال', 'لوازم آشپزخانه', 'سرویس بلور', 'سرویس بلور و کریستال'];

        $bySlug = Category::query()
            ->where('is_active', true)
            ->whereIn('slug', $slugs)
            ->orderBy('position')
            ->pluck('id')
            ->all();

        if (count($bySlug) >= 3) {
            return array_slice($bySlug, 0, 3);
        }

        $byName = Category::query()
            ->where('is_active', true)
            ->whereIn('name', $names)
            ->orderBy('position')
            ->pluck('id')
            ->all();

        if (Schema::hasTable('product_families')) {
            $familySlugs = ['pars-opal', 'kitchenware', 'crystal-service'];
            ProductFamily::query()
                ->where('is_active', true)
                ->whereIn('slug', $familySlugs)
                ->pluck('id')
                ->each(function (int $familyId) use (&$byName): void {
                    $mapped = $this->familyCategoryMapper->rootCategoryIdForFamilyId($familyId);
                    if ($mapped !== null) {
                        $byName[] = $mapped;
                    }
                });
        }

        return collect([...$bySlug, ...$byName])
            ->unique()
            ->take(3)
            ->values()
            ->all();
    }

    /** @param  ?string  $imagePath */
    public static function quickTileImageUrl(mixed $imagePath): string
    {
        $path = trim((string) $imagePath);
        if ($path === '') {
            return asset('shop/images/placeholder-category.svg');
        }

        return ShopFormatter::productFamilyImage($path);
    }
}
