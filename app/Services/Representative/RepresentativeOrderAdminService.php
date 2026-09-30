<?php

namespace App\Services\Representative;

use App\Models\FreightCarrier;
use App\Models\Order;
use App\Services\Payment\PaymentGatewayCatalog;
use Illuminate\Validation\ValidationException;

class RepresentativeOrderAdminService
{
    public function __construct(
        private readonly RepresentativeDraftOrderService $draftOrders,
        private readonly PaymentGatewayCatalog $gateways,
    ) {}

    public function updateFulfillment(Order $order, ?int $freightCarrierId, string $paymentGateway): Order
    {
        $this->assertAdminEditable($order);

        if ($freightCarrierId === null) {
            throw ValidationException::withMessages([
                'freight_carrier_id' => 'باربری را انتخاب کنید.',
            ]);
        }

        $carrier = FreightCarrier::query()
            ->whereKey($freightCarrierId)
            ->where('is_active', true)
            ->first();

        if ($carrier === null) {
            throw ValidationException::withMessages([
                'freight_carrier_id' => 'باربری انتخاب‌شده معتبر نیست.',
            ]);
        }

        $this->gateways->assertEnabled($paymentGateway);

        $order->update([
            'freight_carrier_id' => $carrier->id,
            'shipping_method_id' => null,
            'shipping_amount' => 0,
            'payment_method' => $paymentGateway,
        ]);

        $this->draftOrders->recalculateTotals($order);

        return $order->fresh(['freightCarrier.province', 'freightCarrier.city', 'items', 'user', 'representative']);
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
