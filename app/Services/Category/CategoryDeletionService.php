<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CategoryDeletionService
{
    public function delete(Category $category): CategoryDeletionResult
    {
        if ($category->children()->exists()) {
            throw new RuntimeException('این دسته زیردسته دارد. ابتدا زیردسته‌ها را حذف کنید.');
        }

        $deletedProducts = 0;
        $archivedProducts = 0;

        DB::transaction(function () use ($category, &$deletedProducts, &$archivedProducts): void {
            $fallbackCategory = $this->resolveFallbackCategory($category);

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

        return new CategoryDeletionResult($deletedProducts, $archivedProducts);
    }

    protected function resolveFallbackCategory(Category $excluding): Category
    {
        $existing = Category::query()
            ->where('slug', 'general')
            ->whereKeyNot($excluding->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Category::query()->create([
            'name' => 'عمومی',
            'slug' => 'general',
            'is_active' => true,
        ]);
    }
}
