<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\ProductFamily;
use App\Support\ShopFormatter;
use App\Support\ShopMedia;

final class CategoryHomepageHeroService
{
    public function __construct(
        protected ProductFamilyHomepagePopupService $legacyFamilyPopup,
        protected CatalogFamilyCategoryMapper $familyCategoryMapper,
    ) {}

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function sidebarLinksForRootCategory(Category $root, bool $resolveImageUrls = true): array
    {
        $children = Category::query()
            ->where('parent_id', $root->id)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        if ($children->isEmpty()) {
            return [$this->rootAsSidebarLink($root, $resolveImageUrls)];
        }

        return $children
            ->map(fn (Category $child): array => $this->categoryAsSidebarLink($child, $resolveImageUrls))
            ->all();
    }

    /** @return array{label: string, url: string, image: ?string} */
    public function rootAsSidebarLink(Category $root, bool $resolveImageUrls = true): array
    {
        $imagePath = trim((string) ($root->image ?? ''));

        return [
            'label' => (string) $root->name,
            'url' => route('categories.show', $root),
            'image' => $this->resolveImage($imagePath, $resolveImageUrls),
        ];
    }

    /** @return array{label: string, url: string, image: ?string} */
    public function categoryAsSidebarLink(Category $category, bool $resolveImageUrls = true): array
    {
        $imagePath = trim((string) ($category->image ?? ''));

        return [
            'label' => (string) $category->name,
            'url' => route('categories.show', $category),
            'image' => $this->resolveImage($imagePath, $resolveImageUrls),
        ];
    }

    /**
     * لینک‌های ذخیره‌شده روی خانواده — برای backfill تنظیمات تا مهاجرت کامل.
     *
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function legacyFamilySidebarLinks(ProductFamily $family, bool $resolveImageUrls = true): array
    {
        $items = $this->legacyFamilyPopup->activeLinkItemsForFamily($family, $resolveImageUrls);
        if ($items !== []) {
            return $items;
        }

        $imagePath = trim((string) ($family->image ?? ''));

        return [
            [
                'label' => (string) $family->name,
                'url' => $this->legacyFamilyListingUrl($family),
                'image' => $imagePath !== '' ? ($resolveImageUrls ? ShopFormatter::productFamilyImage($imagePath) : $imagePath) : null,
            ],
        ];
    }

    public function legacyFamilyListingUrl(ProductFamily $family): string
    {
        $categoryId = $this->familyCategoryMapper->rootCategoryIdForFamily($family);
        if ($categoryId !== null) {
            $category = Category::query()->whereKey($categoryId)->first();

            return $category !== null
                ? route('categories.show', $category)
                : route('products.index', ['category_id' => $categoryId]);
        }

        return route('products.index', ['product_family_id' => $family->id]);
    }

    private function resolveImage(string $imagePath, bool $resolveImageUrls): ?string
    {
        if ($imagePath === '') {
            return null;
        }

        if (! $resolveImageUrls) {
            return $imagePath;
        }

        return str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')
            ? $imagePath
            : ShopMedia::url($imagePath);
    }
}
