<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFamily;
use Illuminate\Support\Str;

/**
 * نگاشت legacy خانواده → دستهٔ ریشه (بدون تغییر slug محصول).
 */
final class CatalogFamilyCategoryMapper
{
    /** @var array<string, list<string>> slug خانواده → slugهای متداول دسته */
    private const FAMILY_SLUG_ROOT_ALIASES = [
        'pars-opal' => ['پارس-اپال', 'پارس-اوپال', 'pars-opal', 'opal-pars', 'پارس-اپال'],
        'opal-pars' => ['پارس-اپال', 'پارس-اوپال', 'pars-opal'],
        'kitchenware' => ['لوازم-آشپزخانه', 'خانه-و-آشپزخانه', 'kitchenware', 'لوازم-آشپزخانه'],
        'crystal-service' => ['سرویس-بلور', 'سرویس-بلور-و-کریستال', 'crystal-service', 'ظروف-پذیرایی'],
        'chinese-service' => ['سرویس-چینی', 'chinese-service'],
    ];

    /** @var array<string, list<string>> نام خانواده → نام‌های احتمالی دسته (هر سطح) */
    private const FAMILY_NAME_ALIASES = [
        'پارس اپال' => ['پارس اوپال', 'پارس اپال', 'پارس-اپال'],
        'لوازم آشپزخانه' => ['خانه و آشپزخانه', 'لوازم آشپزخانه'],
        'سرویس بلور' => ['سرویس بلور و کریستال', 'ظروف پذیرایی', 'سرویس بلور'],
    ];

    public function rootCategoryIdForFamilyId(int $familyId): ?int
    {
        $family = ProductFamily::query()->whereKey($familyId)->first();

        return $family !== null ? $this->rootCategoryIdForFamily($family) : null;
    }

    public function rootCategoryIdForFamily(ProductFamily $family): ?int
    {
        $override = $this->configuredRootId((int) $family->id);
        if ($override !== null) {
            return $override;
        }

        $byFk = Category::query()
            ->whereNull('parent_id')
            ->where('product_family_id', $family->id)
            ->orderBy('position')
            ->orderBy('id')
            ->value('id');

        if ($byFk !== null) {
            return (int) $byFk;
        }

        $anyInTree = Category::query()
            ->where('product_family_id', $family->id)
            ->orderBy('parent_id')
            ->orderBy('id')
            ->first();

        if ($anyInTree !== null) {
            return $this->rootIdForCategory($anyInTree);
        }

        $slug = trim((string) $family->slug);
        $name = trim((string) $family->name);

        $nameSlug = $name !== '' ? Str::slug($name, '-', 'fa') : '';

        $slugCandidates = $this->slugCandidates($slug, $nameSlug);

        foreach ($slugCandidates as $candidate) {
            $match = $this->findCategoryBySlugAnyLevel($candidate);
            if ($match !== null) {
                return $this->rootIdForCategory($match);
            }
        }

        foreach ($this->nameCandidates($name) as $candidateName) {
            $match = Category::query()
                ->where('is_active', true)
                ->where('name', $candidateName)
                ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('position')
                ->first();

            if ($match !== null) {
                return $this->rootIdForCategory($match);
            }
        }

        if ($name !== '') {
            $fuzzyRoot = $this->fuzzyRootIdByName($name);
            if ($fuzzyRoot !== null) {
                return $fuzzyRoot;
            }
        }

        return $this->inferRootFromProducts((int) $family->id);
    }

    public function inferRootFromProducts(int $familyId): ?int
    {
        $categoryId = Product::query()
            ->where('product_family_id', $familyId)
            ->where('is_active', true)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as usage_count')
            ->groupBy('category_id')
            ->orderByDesc('usage_count')
            ->value('category_id');

        if ($categoryId === null) {
            return null;
        }

        $category = Category::query()->whereKey((int) $categoryId)->first();

        return $category !== null ? $this->rootIdForCategory($category) : null;
    }

    protected function configuredRootId(int $familyId): ?int
    {
        $map = config('shop.catalog.family_root_category_map', []);
        if (! is_array($map)) {
            return null;
        }

        $id = (int) ($map[$familyId] ?? $map[(string) $familyId] ?? 0);

        return $id > 0 ? $id : null;
    }

    /** @return list<string> */
    protected function slugCandidates(string $slug, string $nameSlug): array
    {
        return array_values(array_filter(array_unique([
            $slug,
            $slug !== '' ? Str::slug($slug, '-', 'fa') : '',
            $nameSlug,
            ...($slug !== '' ? (self::FAMILY_SLUG_ROOT_ALIASES[$slug] ?? []) : []),
            ...($nameSlug !== '' ? (self::FAMILY_SLUG_ROOT_ALIASES[$nameSlug] ?? []) : []),
        ])));
    }

    /** @return list<string> */
    protected function nameCandidates(string $name): array
    {
        $names = [$name, ... (self::FAMILY_NAME_ALIASES[$name] ?? [])];

        return array_values(array_filter(array_unique($names)));
    }

    protected function findCategoryBySlugAnyLevel(string $slug): ?Category
    {
        return Category::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->first();
    }

    public function rootIdForCategory(Category $category): int
    {
        $current = $category;

        while ($current->parent_id !== null) {
            $parent = Category::query()->whereKey($current->parent_id)->first();
            if ($parent === null) {
                break;
            }
            $current = $parent;
        }

        return (int) $current->id;
    }

    private function fuzzyRootIdByName(string $name): ?int
    {
        $needle = $this->normalizeComparable($name);
        if ($needle === '') {
            return null;
        }

        $roots = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['id', 'name']);

        foreach ($roots as $root) {
            $hay = $this->normalizeComparable((string) $root->name);
            if ($hay === $needle) {
                return (int) $root->id;
            }
        }

        foreach ($roots as $root) {
            $hay = $this->normalizeComparable((string) $root->name);
            if ($hay === '' || $needle === '') {
                continue;
            }
            if (str_contains($hay, $needle) || str_contains($needle, $hay)) {
                return (int) $root->id;
            }
        }

        return null;
    }

    private function normalizeComparable(string $value): string
    {
        $value = str_replace(["\u{200C}", "\u{200F}", ' ', '،', ','], '', $value);
        $value = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $value);

        return mb_strtolower(trim($value));
    }
}
