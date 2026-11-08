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

        $slug = trim((string) $family->slug);
        $name = trim((string) $family->name);

        if ($slug !== '') {
            $bySlug = Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->where(function ($query) use ($slug): void {
                    $query->where('slug', $slug)
                        ->orWhere('slug', Str::slug($slug, '-', 'fa'));
                })
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
        }

        return null;
    }
}
