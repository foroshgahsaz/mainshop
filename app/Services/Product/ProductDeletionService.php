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
}
