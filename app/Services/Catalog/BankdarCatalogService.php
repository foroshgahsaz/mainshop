<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Database\Eloquent\Builder;

class BankdarCatalogService
{
    /** @return list<int> */
    public function brandIdsForBankdar(int $bankdarId): array
    {
        return Brand::query()
            ->where('bankdar_id', $bankdarId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return list<int> */
    public function categoryIdsForBankdar(int $bankdarId): array
    {
        return Category::query()
            ->where('bankdar_id', $bankdarId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array<int, string> */
    public function brandOptionsForBankdar(int $bankdarId): array
    {
        return Brand::query()
            ->where('bankdar_id', $bankdarId)
            ->orderBy('position')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public function categoryOptionsForBankdar(int $bankdarId): array
    {
        return Category::query()
            ->where('bankdar_id', $bankdarId)
            ->orderBy('position')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function scopeBrandsForActor(Builder $brands, ?User $actor = null): Builder
    {
        $actor ??= AdminAccess::user();

        if (! $actor || AdminAccess::isFullAdmin($actor) || AdminAccess::isSalesManagerOnly($actor)) {
            return $brands;
        }

        if (AdminAccess::isBankdarOnly($actor)) {
            return $brands->where('bankdar_id', (int) $actor->id);
        }

        return $brands->whereRaw('1 = 0');
    }

    public function scopeCategoriesForActor(Builder $categories, ?User $actor = null): Builder
    {
        $actor ??= AdminAccess::user();

        if (! $actor || AdminAccess::isFullAdmin($actor) || AdminAccess::isSalesManagerOnly($actor)) {
            return $categories;
        }

        if (AdminAccess::isBankdarOnly($actor)) {
            return $categories->where('bankdar_id', (int) $actor->id);
        }

        return $categories->whereRaw('1 = 0');
    }

    public function scopeProductsForActor(Builder $products, ?User $actor = null): Builder
    {
        $actor ??= AdminAccess::user();

        if (! $actor || AdminAccess::isFullAdmin($actor) || AdminAccess::isSalesManagerOnly($actor)) {
            return $products;
        }

        if (! AdminAccess::isBankdarOnly($actor)) {
            return $products->whereRaw('1 = 0');
        }

        $bankdarId = (int) $actor->id;
        $brandIds = $this->brandIdsForBankdar($bankdarId);
        $categoryIds = $this->categoryIdsForBankdar($bankdarId);

        if ($brandIds === [] && $categoryIds === []) {
            return $products->whereRaw('1 = 0');
        }

        return $products->where(function (Builder $q) use ($brandIds, $categoryIds): void {
            if ($brandIds !== []) {
                $q->whereIn('brand_id', $brandIds);
            }
            if ($categoryIds !== []) {
                $method = $brandIds !== [] ? 'orWhereIn' : 'whereIn';
                $q->{$method}('category_id', $categoryIds);
            }
        });
    }

    public function productBelongsToBankdar(Product $product, int $bankdarId): bool
    {
        if ($product->brand_id && in_array((int) $product->brand_id, $this->brandIdsForBankdar($bankdarId), true)) {
            return true;
        }

        if ($product->category_id && in_array((int) $product->category_id, $this->categoryIdsForBankdar($bankdarId), true)) {
            return true;
        }

        return false;
    }

    /** گزینه‌های بنکدار برای فرم ادمین. */
    /** @return array<int, string> */
    public function bankdarOptions(): array
    {
        return User::query()
            ->where('is_bankdar', true)
            ->where('status', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
