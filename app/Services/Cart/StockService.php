<?php

namespace App\Services\Cart;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function assertAvailable(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $stock = $variant ? $variant->stock : $product->stock;

        if ($stock < $quantity) {
            throw new RuntimeException('موجودی کافی نیست.');
        }
    }

    public function assertOrderAvailable(Order $order): void
    {
        $order->loadMissing('items.product', 'items.variant');

        foreach ($order->items as $item) {
            if (! $item->product) {
                throw new RuntimeException('محصول سفارش یافت نشد.');
            }

            $this->assertAvailable($item->product, $item->variant, $item->quantity);
        }
    }

    public function decrement(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        DB::transaction(function () use ($product, $variant, $quantity) {
            if ($variant) {
                $locked = ProductVariant::whereKey($variant->id)->lockForUpdate()->firstOrFail();
                if ($locked->stock < $quantity) {
                    throw new RuntimeException('موجودی کافی نیست.');
                }
                $locked->decrement('stock', $quantity);

                return;
            }

            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if ($locked->stock < $quantity) {
                throw new RuntimeException('موجودی کافی نیست.');
            }
            $locked->decrement('stock', $quantity);
        });
    }

    public function decrementOrderItems(Order $order): void
    {
        $order->loadMissing('items.product', 'items.variant');

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $this->decrement($item->product, $item->variant, $item->quantity);
        }
    }

    public function restore(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        if ($variant) {
            $variant->increment('stock', $quantity);

            return;
        }

        $product->increment('stock', $quantity);
    }

    public function restoreOrderItems(Order $order): void
    {
        $order->loadMissing('items.product', 'items.variant');

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $this->restore($item->product, $item->variant, $item->quantity);
        }
    }
}
