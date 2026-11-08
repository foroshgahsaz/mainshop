<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\ProductFamily;
use Illuminate\Support\Str;

/**
 * نگاشت legacy خانواده → دستهٔ ریشه (بدون تغییر slug محصول).
 */
final class CatalogFamilyCategoryMapper
{
    /** @var array<string, list<string>> slug خانواده → slugهای متداول دستهٔ ریشه */
    private const FAMILY_SLUG_ROOT_ALIASES = [
        'pars-opal' => ['پارس-اپال', 'پارس-اوپال', 'pars-opal', 'opal-pars'],
        'opal-pars' => ['پارس-اپال', 'پارس-اوپال', 'pars-opal'],
        'kitchenware' => ['لوازم-آشپزخانه', 'خانه-و-آشپزخانه', 'kitchenware'],
        'crystal-service' => ['سرویس-بلور', 'سرویس-بلور-و-کریستال', 'crystal-service'],
        'chinese-service' => ['سرویس-چینی', 'chinese-service', 'سرویس-چینی'],
    ];

    public function rootCategoryIdForFamilyId(int $familyId): ?int
    {
        $family = ProductFamily::query()->whereKey($familyId)->first();

        return $family !== null ? $this->rootCategoryIdForFamily($family) : null;
    }

    public function rootCategoryIdForFamily(ProductFamily $family): ?int
    {
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

        $slugCandidates = array_filter(array_unique([
            $slug,
            $slug !== '' ? Str::slug($slug, '-', 'fa') : '',
            $nameSlug,
            ...($slug !== '' ? (self::FAMILY_SLUG_ROOT_ALIASES[$slug] ?? []) : []),
            ...($nameSlug !== '' ? (self::FAMILY_SLUG_ROOT_ALIASES[$nameSlug] ?? []) : []),
        ]));

        foreach ($slugCandidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $bySlug = Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->where('slug', $candidate)
                ->orderBy('position')
                ->value('id');

            if ($bySlug !== null) {
                return (int) $bySlug;
            }
        }

        if ($name !== '') {
            $byName = Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->where('name', $name)
                ->orderBy('position')
                ->value('id');

            if ($byName !== null) {
                return (int) $byName;
            }

            $fuzzy = $this->fuzzyRootIdByName($name);
            if ($fuzzy !== null) {
                return $fuzzy;
            }
        }

        return null;
    }

    private function rootIdForCategory(Category $category): int
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
