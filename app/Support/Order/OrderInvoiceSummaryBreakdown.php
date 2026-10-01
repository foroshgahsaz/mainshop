<?php

namespace App\Support\Order;

use App\Models\Order;
use App\Models\OrderInvoiceLine;

final class OrderInvoiceSummaryBreakdown
{
    /**
     * @return list<array{label: string, amount: int}>
     */
    public static function discountLines(Order $order): array
    {
        $order->loadMissing(['items', 'invoiceLines', 'coupon']);

        $lines = [];

        foreach ($order->items as $item) {
            $amount = OrderItemLinePricing::discountAmount($item);

            if ($amount <= 0) {
                continue;
            }

            $lines[] = [
                'label' => 'تخفیف ردیف: '.$item->product_name,
                'amount' => $amount,
            ];
        }

        foreach ($order->invoiceLines as $invoiceLine) {
            if ($invoiceLine->kind !== OrderInvoiceLine::KIND_ORDER_DISCOUNT) {
                continue;
            }

            $lines[] = [
                'label' => (string) $invoiceLine->title,
                'amount' => (int) $invoiceLine->amount,
            ];
        }

        $accounted = array_sum(array_column($lines, 'amount'));
        $couponRemainder = (int) $order->discount_amount - $accounted;

        if ($couponRemainder > 0 && $order->coupon) {
            $lines[] = [
                'label' => 'کوپن: '.$order->coupon->code,
                'amount' => $couponRemainder,
            ];
        } elseif ($couponRemainder > 0 && $lines === []) {
            $lines[] = [
                'label' => 'تخفیف سفارش',
                'amount' => (int) $order->discount_amount,
            ];
        }

        return $lines;
    }

    /**
     * @return list<array{label: string, amount: int}>
     */
    public static function feeLines(Order $order): array
    {
        $order->loadMissing('invoiceLines');

        $lines = [];

        foreach ($order->invoiceLines as $invoiceLine) {
            if ($invoiceLine->kind !== OrderInvoiceLine::KIND_FEE) {
                continue;
            }

            $lines[] = [
                'label' => (string) $invoiceLine->title,
                'amount' => (int) $invoiceLine->amount,
            ];
        }

        return $lines;
    }
}
