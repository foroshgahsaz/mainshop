<?php

namespace App\Services\Representative;

use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderInvoiceLine;
use App\Services\Checkout\CouponService;
use App\Support\Order\OrderItemLinePricing;
use Illuminate\Validation\ValidationException;

class RepresentativeDraftCouponService
{
    public function __construct(
        protected CouponService $coupons,
        protected RepresentativeDraftOrderService $drafts,
    ) {}

    public function apply(Order $order, string $code, ?string $paymentMethod = null): Order
    {
        $this->drafts->assertDraftOwnedBy($order, auth()->user());

        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            return $this->remove($order);
        }

        $order->loadMissing(['user', 'items', 'invoiceLines']);

        $itemsGross = $this->itemsGrossSubtotal($order);
        $gateway = $paymentMethod !== null && $paymentMethod !== ''
            ? $paymentMethod
            : (string) $order->payment_method;

        $validation = $this->coupons->validate($normalized, $order->user, $itemsGross, $gateway);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'coupon_code' => $validation['message'] ?? 'کد تخفیف معتبر نیست.',
            ]);
        }

        $coupon = $validation['coupon'];

        if (! $coupon->stackable_with_other_coupons) {
            $hasManualDiscount = $order->invoiceLines
                ->contains(fn ($line) => $line->kind === OrderInvoiceLine::KIND_ORDER_DISCOUNT);

            if ($hasManualDiscount) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'این کد با تخفیف دستی روی پیش‌فاکتور قابل ترکیب نیست.',
                ]);
            }
        }

        $order->update(['coupon_id' => $coupon->id]);
        $this->drafts->recalculateTotals($order);

        return $order->fresh(['coupon', 'items', 'invoiceLines']);
    }

    public function remove(Order $order): Order
    {
        $this->drafts->assertDraftOwnedBy($order, auth()->user());

        if ($order->coupon_id === null) {
            return $order;
        }

        $order->update(['coupon_id' => null]);
        $this->drafts->recalculateTotals($order);

        return $order->fresh(['coupon', 'items', 'invoiceLines']);
    }

    public function assertStillValid(Order $order): ?string
    {
        if ($order->coupon_id === null) {
            return null;
        }

        $order->loadMissing(['coupon', 'user', 'items']);

        $coupon = $order->coupon;

        if ($coupon === null) {
            return 'کد تخفیف ثبت‌شده دیگر موجود نیست.';
        }

        return $this->coupons->failureMessage(
            $coupon,
            $order->user,
            $this->itemsGrossSubtotal($order),
            (string) $order->payment_method,
        );
    }

    public function recordUsageIfNeeded(Order $order): void
    {
        if ($order->coupon_id === null || $order->user === null) {
            return;
        }

        $coupon = $order->coupon;

        if ($coupon === null) {
            return;
        }

        $already = CouponUsage::query()
            ->where('order_id', $order->id)
            ->where('coupon_id', $coupon->id)
            ->exists();

        if ($already) {
            return;
        }

        $this->coupons->recordUsage($coupon, $order->user, $order);
    }

    public function couponDiscountAmount(Order $order): int
    {
        if ($order->coupon_id === null) {
            return 0;
        }

        $order->loadMissing(['coupon', 'user', 'items']);

        $coupon = $order->coupon;

        if ($coupon === null) {
            return 0;
        }

        $itemsGross = $this->itemsGrossSubtotal($order);

        if ($this->coupons->failureMessage($coupon, $order->user, $itemsGross, (string) $order->payment_method) !== null) {
            return 0;
        }

        return $coupon->calculateDiscount($itemsGross);
    }

    private function itemsGrossSubtotal(Order $order): int
    {
        $sum = 0;

        foreach ($order->items as $item) {
            $sum += OrderItemLinePricing::grossAmount($item);
        }

        return $sum;
    }
}
