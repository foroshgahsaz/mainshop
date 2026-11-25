<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Support\AdminAccess;
use Illuminate\Support\Collection;

class ProductDeletionService
{
    /**
     * @return 'deleted'|'archived'
     */
    public function delete(Product $product): string
    {
        $product->cartItems()->delete();

        // بنکدار/مدیر فروش: همیشه soft-delete تا از فروشگاه برود و ادمین بتواند برگرداند.
        if (AdminAccess::isLimitedStaffOnly()) {
            $product->update(['is_active' => false]);
            $product->delete();

            return 'archived';
        }

        if ($product->orderItems()->exists()) {
            $product->update(['is_active' => false]);
            $product->delete();

            return 'archived';
        }

        $product->forceDelete();

        return 'deleted';
    }

    /**
     * @param  Collection<int, Product>|iterable<int, Product>  $products
     */
    public function deleteMany(iterable $products): ProductDeletionResult
    {
        $deleted = 0;
        $archived = 0;

        foreach ($products as $product) {
            if ($this->delete($product) === 'archived') {
                $archived++;
            } else {
                $deleted++;
            }
        }

        return new ProductDeletionResult($deleted, $archived);
    }

    public function restore(Product $product): void
    {
        if (! AdminAccess::isFullAdmin()) {
            throw new \RuntimeException('فقط مدیر سیستم می‌تواند محصول حذف‌شده را بازگرداند.');
        }

        if (! $product->trashed()) {
            throw new \RuntimeException('این محصول حذف نشده است.');
        }

        $product->restore();
    }

    public function forceDeleteForever(Product $product): void
    {
        if (! AdminAccess::isFullAdmin()) {
            throw new \RuntimeException('فقط مدیر سیستم می‌تواند حذف دائمی انجام دهد.');
        }

        if ($product->orderItems()->exists()) {
            throw new \RuntimeException(
                'این محصول در سفارش ثبت شده؛ حذف دائمی مجاز نیست. فقط می‌توانید آن را آرشیو نگه دارید.'
            );
        }

        $product->cartItems()->delete();
        $product->forceDelete();
    }
}
