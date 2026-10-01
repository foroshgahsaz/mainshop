<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\Cart\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderDeletionService
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    /**
     * @return 'deleted'|'skipped'
     */
    public function delete(Order $order): string
    {
        if ($order->hasSuccessfulPayment()) {
            return 'skipped';
        }

        return DB::transaction(function () use ($order): string {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->hasSuccessfulPayment()) {
                return 'skipped';
            }

            if ($locked->stock_reserved) {
                $this->stockService->restoreOrderItems($locked);
            }

            $locked->delete();

            return 'deleted';
        });
    }

    /**
     * @param  Collection<int, Order>|iterable<int, Order>  $orders
     */
    public function deleteMany(iterable $orders): OrderDeletionResult
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if ($this->delete($order) === 'skipped') {
                $skipped++;
            } else {
                $deleted++;
            }
        }

        return new OrderDeletionResult($deleted, $skipped);
    }
}
