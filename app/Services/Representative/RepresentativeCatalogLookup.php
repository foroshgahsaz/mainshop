<?php

namespace App\Services\Representative;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductPlant;
use App\Models\ProductTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RepresentativeCatalogLookup
{
    /** @return Collection<int, ProductFamily> */
    public function families(): Collection
    {
        return Cache::remember(
            'rep:catalog:families',
            $this->cacheTtl(),
            fn () => ProductFamily::query()
                ->where('is_active', true)
                ->whereHas('products', fn (Builder $q) => $this->baseProductQuery($q))
                ->orderBy('position')
                ->get(['id', 'name'])
        );
    }

    /** @return Collection<int, ProductPlant> */
    public function plants(int $familyId): Collection
    {
        return ProductPlant::query()
            ->where('product_family_id', $familyId)
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $q) => $this->baseProductQuery($q)->where('product_family_id', $familyId))
            ->orderBy('position')
            ->get(['id', 'name']);
    }

    /** @return Collection<int, Brand> */
    public function brands(int $familyId, int $plantId): Collection
    {
        $brandIds = Product::query()
            ->where('product_family_id', $familyId)
            ->where('product_plant_id', $plantId)
            ->tap(fn (Builder $q) => $this->baseProductQuery($q))
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id');

        return Brand::query()
            ->whereIn('id', $brandIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** @return Collection<int, ProductTemplate> */
    public function templates(int $familyId, int $brandId): Collection
    {
        return ProductTemplate::query()
            ->where('product_family_id', $familyId)
            ->where('brand_id', $brandId)
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $q) => $this->baseProductQuery($q)
                ->where('product_family_id', $familyId)
                ->where('brand_id', $brandId))
            ->orderBy('position')
            ->get(['id', 'name']);
    }

    public function products(
        int $familyId,
        int $plantId,
        int $brandId,
        int $templateId,
        string $search = '',
        int $page = 1,
    ): LengthAwarePaginator {
        $perPage = max(5, (int) config('shop.representative.products_per_page', 15));

        return Product::query()
            ->where('product_family_id', $familyId)
            ->where('product_plant_id', $plantId)
            ->where('brand_id', $brandId)
            ->where('product_template_id', $templateId)
            ->tap(fn (Builder $q) => $this->baseProductQuery($q))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($perPage, ['id', 'name', 'sku', 'price', 'sale_price', 'stock'], 'page', $page);
    }

    private function baseProductQuery(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    private function cacheTtl(): int
    {
        return max(60, (int) config('shop.representative.catalog_cache_seconds', 300));
    }
}
