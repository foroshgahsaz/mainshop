<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CategoryDeletionService
{
    public const FALLBACK_SLUG = 'general';

    public function delete(Category $category): CategoryDeletionResult
    {
        if ($category->children()->exists()) {
            throw new RuntimeException('این دسته زیردسته دارد. ابتدا زیردسته‌ها را حذف کنید.');
        }

        $deletedProducts = 0;
        $archivedProducts = 0;
        $fallbackCategoryName = null;

        DB::transaction(function () use ($category, &$deletedProducts, &$archivedProducts, &$fallbackCategoryName): void {
            $fallbackCategory = $this->resolveFallbackCategory($category);
            $fallbackCategoryName = $fallbackCategory->name;

            $category->products()
                ->withTrashed()
                ->get()
                ->each(function (Product $product) use ($fallbackCategory, &$deletedProducts, &$archivedProducts): void {
                    if ($product->orderItems()->exists()) {
                        $product->update([
                            'category_id' => $fallbackCategory->id,
                            'is_active' => false,
                        ]);

                        if (! $product->trashed()) {
                            $product->delete();
                        }

                        $archivedProducts++;

                        return;
                    }

                    $product->cartItems()->delete();
                    $product->forceDelete();
                    $deletedProducts++;
                });

            $category->delete();
        });

        return new CategoryDeletionResult($deletedProducts, $archivedProducts, $fallbackCategoryName);
    }

    protected function resolveFallbackCategory(Category $excluding): Category
    {
        $preferred = Category::query()
            ->where('slug', self::FALLBACK_SLUG)
            ->whereKeyNot($excluding->id)
            ->first();

        if ($preferred) {
            return $preferred;
        }

        $fallback = Category::query()
            ->whereKeyNot($excluding->id)
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($fallback) {
            return $fallback;
        }

        throw new RuntimeException(
            'برای حذف این دسته، ابتدا حداقل یک دسته دیگر ایجاد کنید تا محصولات موجود در سفارش‌ها به آن منتقل شوند.'
        );
    }
}
