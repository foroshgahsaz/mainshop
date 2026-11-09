<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Support\ShopStaffPreview;
use Illuminate\Support\Collection;

final class CategoryListingNavService
{
    public function __construct(
        protected CategoryPathResolver $paths,
    ) {}

    /**
     * @return list<array{id: int, name: string, slug: string, path: string}>
     */
    public function breadcrumbTrail(Category $category): array
    {
        return $this->paths->breadcrumbTrail($category);
    }

    /** فقط زیردسته‌های مستقیم فعال — روی دستهٔ برگ خالی است. */
    public function carouselCategories(Category $category, ?bool $asStaff = null): Collection
    {
        $asStaff ??= ShopStaffPreview::enabled();

        return Category::query()
            ->visibleOnShop($asStaff)
            ->where('parent_id', $category->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }
}
