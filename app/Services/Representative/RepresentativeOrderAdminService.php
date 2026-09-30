<?php

namespace App\Services\Representative;

use App\Models\FreightCarrier;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart\StockService;
use App\Services\Order\OrderActivityLogger;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Support\ShopLabels;
use Illuminate\Validation\ValidationException;

class RepresentativeOrderAdminService
{
    public function __construct(
        private readonly RepresentativeDraftOrderService $draftOrders,
        private readonly PaymentGatewayCatalog $gateways,
        private readonly OrderActivityLogger $orderLog,
        private readonly StockService $stockService,
    ) {}

    public function updateFulfillment(Order $order, ?int $freightCarrierId, string $paymentGateway, ?User $actor = null): Order
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

        $order->loadMissing('freightCarrier');

        $previousCarrierId = $order->freight_carrier_id;
        $previousPaymentMethod = (string) $order->payment_method;
        $previousCarrierLabel = $order->freightCarrier?->displayLabel() ?? '—';
        $previousGateway = ShopLabels::paymentMethod($previousPaymentMethod);

        $order->update([
            'freight_carrier_id' => $carrier->id,
            'shipping_method_id' => null,
            'shipping_amount' => 0,
            'payment_method' => $paymentGateway,
        ]);

        $this->draftOrders->recalculateTotals($order);

        $newCarrierLabel = $carrier->displayLabel();
        $newGateway = ShopLabels::paymentMethod($paymentGateway);

        if ($previousCarrierLabel !== $newCarrierLabel || $previousGateway !== $newGateway) {
            $message = sprintf(
                'ادمین باربری/درگاه پیش‌فاکتور را به‌روزرسانی کرد. باربری: «%s» → «%s». درگاه: «%s» → «%s».',
                $previousCarrierLabel,
                $newCarrierLabel,
                $previousGateway,
                $newGateway,
            );

            if ($actor) {
                $this->orderLog->byUser($order->fresh(), $actor, $message, 'private', 'proforma_admin_edit', [
                    'freight_carrier_id' => ['from' => $previousCarrierId, 'to' => $carrier->id],
                    'payment_method' => ['from' => $previousPaymentMethod, 'to' => $paymentGateway],
                ]);
            } else {
                $this->orderLog->system($order->fresh(), $message, 'proforma_admin_edit');
            }
        }

        return $order->fresh(['freightCarrier.province', 'freightCarrier.city', 'items', 'user', 'representative']);
    }

    /**
     * @param  array<int, int|string>  $quantitiesByItemId
     */
    public function syncProformaItemQuantities(Order $order, array $quantitiesByItemId, User $actor): Order
    {
        $this->assertAdminEditable($order);

        if (! $order->isProforma()) {
            throw ValidationException::withMessages([
                'order' => 'ویرایش تعداد فقط برای پیش‌فاکتور مجاز است.',
            ]);
        }

        $order->loadMissing('items');
        $changes = [];

        foreach ($order->items as $item) {
            if (! array_key_exists($item->id, $quantitiesByItemId)) {
                continue;
            }

            $quantity = (int) $quantitiesByItemId[$item->id];

            if ($quantity < 1 || $quantity > 999) {
                throw ValidationException::withMessages([
                    'quantity' => 'تعداد هر قلم باید بین ۱ تا ۹۹۹ باشد.',
                ]);
            }

            $previousQuantity = (int) $item->quantity;

            if ($quantity === $previousQuantity) {
                continue;
            }

            if ($order->stock_reserved) {
                $item->loadMissing('product', 'variant');

                if (! $item->product) {
                    throw ValidationException::withMessages([
                        'quantity' => 'محصول یکی از اقلام یافت نشد.',
                    ]);
                }

                $delta = $quantity - $previousQuantity;

                if ($delta > 0) {
                    $this->stockService->assertAvailable($item->product, $item->variant, $delta);
                    $this->stockService->decrement($item->product, $item->variant, $delta);
                } elseif ($delta < 0) {
                    $this->stockService->restore($item->product, $item->variant, abs($delta));
                }
            }

            $changes[] = sprintf('%s: %d → %d', $item->product_name, $previousQuantity, $quantity);

            $unitPrice = (int) $item->price;
            $item->update([
                'quantity' => $quantity,
                'total_price' => $unitPrice * $quantity,
            ]);
        }

        if ($changes === []) {
            return $order->fresh(['items']);
        }

        $this->draftOrders->recalculateTotals($order);

        $this->orderLog->byUser(
            $order->fresh(),
            $actor,
            'ادمین تعداد اقلام پیش‌فاکتور را ویرایش کرد: '.implode('؛ ', $changes),
            'private',
            'proforma_admin_edit',
            ['line_changes' => $changes],
        );

        return $order->fresh(['items', 'user', 'representative', 'freightCarrier']);
    }

    public function assertAdminEditable(Order $order): void
    {
        if (! $order->isRepresentativeOrder()) {
            throw ValidationException::withMessages([
                'order' => 'این سفارش متعلق به نمایندگی نیست.',
            ]);
        }

        if ($order->isPaid()) {
            throw ValidationException::withMessages([
                'order' => 'پیش‌فاکتور تسویه‌شده قابل ویرایش نیست.',
            ]);
        }

        if ($order->isDraft()) {
            return;
        }

        if ($order->isProforma()) {
            return;
        }

        throw ValidationException::withMessages([
            'order' => 'این سفارش در وضعیت قابل ویرایش ادمین نیست.',
        ]);
    }
}
