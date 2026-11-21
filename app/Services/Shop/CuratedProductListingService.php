<?php

namespace App\Services\Shop;

use App\Models\Product;
use App\Support\OrderedProductIds;
use Illuminate\Support\Collection;

class CuratedProductListingService
{
    /**
     * @param  list<int>  $ids
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function productsForIds(array $ids, bool $staffPreview = false): Collection
    {
        $ids = OrderedProductIds::parse($ids);

        if ($ids === []) {
            return collect();
        }

        $products = Product::query()
            ->visibleOnShop($staffPreview)
            ->with(['images' => fn ($q) => $q->orderBy('position')])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->values();
    }
}