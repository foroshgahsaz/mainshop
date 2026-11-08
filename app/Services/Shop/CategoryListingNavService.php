<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Support\ShopStaffPreview;
use Illuminate\Support\Collection;

final class CategoryListingNavService
{
    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function breadcrumbTrail(Category $category): array
    {
        $trail = [];
        $node = $category;

        while ($node !== null) {
            array_unshift($trail, [
                'id' => (int) $node->id,
                'name' => (string) $node->name,
                'slug' => (string) $node->slug,
            ]);

            if (! $node->parent_id) {
                break;
            }

            $node = Category::query()->whereKey($node->parent_id)->first();
        }

        return $trail;
    }

    /** زیردسته‌های مستقیم، یا اگر برگ است هم‌سطح‌های همان والد. */
    public function carouselCategories(Category $category, ?bool $asStaff = null): Collection
    {
        $asStaff ??= ShopStaffPreview::enabled();

        $children = Category::query()
            ->visibleOnShop($asStaff)
            ->where('parent_id', $category->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        if ($children->isNotEmpty()) {
            return $children;
        }

        if ($category->parent_id === null) {
            return collect();
        }

        return Category::query()
            ->visibleOnShop($asStaff)
            ->where('parent_id', $category->parent_id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }
}
