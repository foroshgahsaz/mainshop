<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderInvoiceLine;
use App\Support\Order\OrderItemLinePricing;

class OrderInvoiceTotalsService
{
    public function recalculate(Order $order): Order
    {
        if (! $order->isDraft() && ! $order->isProforma()) {
            return $order;
        }

        $order->loadMissing(['items', 'invoiceLines']);

        $itemsGross = 0;
        $itemsNet = 0;
        $itemDiscountTotal = 0;

        foreach ($order->items as $item) {
            $gross = OrderItemLinePricing::grossAmount($item);
            $discount = OrderItemLinePricing::discountAmount($item);
            $net = max(0, $gross - $discount);

            if ((int) $item->total_price !== $net) {
                $item->update(['total_price' => $net]);
            }

            $itemsGross += $gross;
            $itemsNet += $net;
            $itemDiscountTotal += $discount;
        }

        $orderDiscount = (int) $order->invoiceLines
            ->where('kind', OrderInvoiceLine::KIND_ORDER_DISCOUNT)
            ->sum('amount');

        $feesTotal = (int) $order->invoiceLines
            ->where('kind', OrderInvoiceLine::KIND_FEE)
            ->sum('amount');

        $shipping = (int) $order->shipping_amount;

        $order->update([
            'total_amount' => $itemsGross,
            'discount_amount' => $itemDiscountTotal + $orderDiscount,
            'final_amount' => max(0, $itemsNet - $orderDiscount + $feesTotal + $shipping),
        ]);

        return $order->fresh(['items', 'invoiceLines']);
    }

    public function feesTotal(Order $order): int
    {
        $order->loadMissing('invoiceLines');

        return (int) $order->invoiceLines
            ->where('kind', OrderInvoiceLine::KIND_FEE)
            ->sum('amount');
    }

    public function orderLevelDiscountTotal(Order $order): int
    {
        $order->loadMissing('invoiceLines');

        return (int) $order->invoiceLines
            ->where('kind', OrderInvoiceLine::KIND_ORDER_DISCOUNT)
            ->sum('amount');
    }
}
