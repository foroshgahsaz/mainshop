<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\ShippingMethod;
use Illuminate\Validation\ValidationException;

class RepresentativeOrderAdminService
{
    public function __construct(
        private readonly RepresentativeDraftOrderService $draftOrders,
    ) {}

    public function updateFulfillment(Order $order, ?int $shippingMethodId, string $paymentMethod): Order
    {
        $this->assertAdminEditable($order);

        $shipping = $shippingMethodId
            ? ShippingMethod::query()->whereKey($shippingMethodId)->where('is_active', true)->first()
            : null;

        if ($shippingMethodId && $shipping === null) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'روش ارسال انتخاب‌شده معتبر نیست.',
            ]);
        }

        if (! in_array($paymentMethod, ['online', 'cod'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'روش پرداخت معتبر نیست.',
            ]);
        }

        $order->update([
            'shipping_method_id' => $shipping?->id,
            'shipping_amount' => (int) ($shipping?->price ?? 0),
            'payment_method' => $paymentMethod,
        ]);

        $this->draftOrders->recalculateTotals($order);

        return $order->fresh(['shippingMethod', 'items', 'user', 'representative']);
    }

    public function assertAdminEditable(Order $order): void
    {
        if (! $order->isRepresentativeOrder() || (! $order->isDraft() && ! $order->isProforma())) {
            throw ValidationException::withMessages([
                'order' => 'این سفارش در وضعیت قابل ویرایش ادمین نیست.',
            ]);
        }
    }
}
