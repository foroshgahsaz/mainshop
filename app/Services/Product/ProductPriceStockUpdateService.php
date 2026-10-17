<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductPriceStockUpdateService
{
    public function updateRow(Product $product, int $price, int $stock, ?int $editorUserId): Product
    {
        $product->update([
            'price' => max(0, $price),
            'stock' => max(0, $stock),
            'last_edited_by_user_id' => $editorUserId,
        ]);

        return $product->fresh();
    }

    /** @return array{price_updated: int, stock_updated: int} */
    public function bulkAdjustOnQuery(Builder $query, ?float $pricePercent, ?int $stockDelta, ?int $editorUserId): array
    {
        $priceUpdated = 0;
        $stockUpdated = 0;

        DB::transaction(function () use ($query, $pricePercent, $stockDelta, $editorUserId, &$priceUpdated, &$stockUpdated): void {
            $query->clone()
                ->select('id', 'price', 'stock')
                ->orderBy('id')
                ->chunkById(200, function ($products) use ($pricePercent, $stockDelta, $editorUserId, &$priceUpdated, &$stockUpdated): void {
                    foreach ($products as $product) {
                        /** @var Product $product */
                        $updates = ['last_edited_by_user_id' => $editorUserId];

                        if ($pricePercent !== null && $pricePercent !== 0.0) {
                            $factor = 1 + ($pricePercent / 100);
                            $updates['price'] = max(0, (int) round((int) $product->price * $factor));
                            $priceUpdated++;
                        }

                        if ($stockDelta !== null && $stockDelta !== 0) {
                            $updates['stock'] = max(0, (int) $product->stock + $stockDelta);
                            $stockUpdated++;
                        }

                        if (count($updates) > 1) {
                            Product::query()->whereKey($product->id)->update($updates);
                        }
                    }
                });
        });

        return [
            'price_updated' => $priceUpdated,
            'stock_updated' => $stockUpdated,
        ];
    }
}
