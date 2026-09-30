<?php

namespace App\Services\Representative;

use App\Models\FreightCarrier;
use App\Models\Order;
use App\Models\OrderInvoiceLine;
use App\Models\User;
use App\Support\Order\OrderItemLinePricing;
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

            $item->quantity = $quantity;
            $item->update([
                'quantity' => $quantity,
                'total_price' => OrderItemLinePricing::netAmount($item),
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

    /**
     * @param  array<int, int|string>  $quantitiesByItemId
     * @param  array<int, string>  $discountTypesByItemId
     * @param  array<int, int|string>  $discountValuesByItemId
     */
    public function syncProformaItems(
        Order $order,
        array $quantitiesByItemId,
        array $discountTypesByItemId,
        array $discountValuesByItemId,
        User $actor,
    ): Order {
        $this->assertAdminEditable($order);

        if (! $order->isProforma()) {
            throw ValidationException::withMessages([
                'order' => 'ویرایش اقلام فقط برای پیش‌فاکتور مجاز است.',
            ]);
        }

        $order->loadMissing('items');
        $changes = [];

        foreach ($order->items as $item) {
            $quantity = array_key_exists($item->id, $quantitiesByItemId)
                ? (int) $quantitiesByItemId[$item->id]
                : (int) $item->quantity;

            if ($quantity < 1 || $quantity > 999) {
                throw ValidationException::withMessages([
                    'quantity' => 'تعداد هر قلم باید بین ۱ تا ۹۹۹ باشد.',
                ]);
            }

            $discountType = (string) ($discountTypesByItemId[$item->id] ?? $item->line_discount_type ?? OrderItemLinePricing::DISCOUNT_NONE);
            $discountValue = (int) ($discountValuesByItemId[$item->id] ?? $item->line_discount_value ?? 0);

            if (! in_array($discountType, OrderItemLinePricing::discountTypes(), true)) {
                throw ValidationException::withMessages([
                    'discount' => 'نوع تخفیف ردیف معتبر نیست.',
                ]);
            }

            if ($discountType === OrderItemLinePricing::DISCOUNT_NONE) {
                $discountValue = 0;
            } elseif ($discountType === OrderItemLinePricing::DISCOUNT_PERCENT) {
                if ($discountValue < 1 || $discountValue > 100) {
                    throw ValidationException::withMessages([
                        'discount' => 'درصد تخفیف هر ردیف باید بین ۱ تا ۱۰۰ باشد.',
                    ]);
                }
            } elseif ($discountValue < 1) {
                throw ValidationException::withMessages([
                    'discount' => 'مبلغ تخفیف هر ردیف باید بزرگ‌تر از صفر باشد.',
                ]);
            }

            $previousQuantity = (int) $item->quantity;
            $previousType = (string) ($item->line_discount_type ?? OrderItemLinePricing::DISCOUNT_NONE);
            $previousValue = (int) ($item->line_discount_value ?? 0);

            $quantityChanged = $quantity !== $previousQuantity;
            $discountChanged = $discountType !== $previousType || $discountValue !== $previousValue;

            if ($quantityChanged && $order->stock_reserved) {
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

            $item->quantity = $quantity;
            $item->line_discount_type = $discountType;
            $item->line_discount_value = $discountValue;

            $item->update([
                'quantity' => $quantity,
                'line_discount_type' => $discountType,
                'line_discount_value' => $discountValue,
                'total_price' => OrderItemLinePricing::netAmount($item),
            ]);

            if ($quantityChanged) {
                $changes[] = sprintf('%s تعداد: %d → %d', $item->product_name, $previousQuantity, $quantity);
            }

            if ($discountChanged) {
                $changes[] = sprintf(
                    '%s تخفیف: %s/%s → %s/%s',
                    $item->product_name,
                    $previousType,
                    $previousValue,
                    $discountType,
                    $discountValue,
                );
            }
        }

        $this->draftOrders->recalculateTotals($order->fresh(['items', 'invoiceLines']));

        if ($changes !== []) {
            $this->orderLog->byUser(
                $order->fresh(),
                $actor,
                'ادمین اقلام پیش‌فاکتور را ویرایش کرد: '.implode('؛ ', $changes),
                'private',
                'proforma_admin_edit',
                ['line_changes' => $changes],
            );
        }

        return $order->fresh(['items', 'user', 'representative', 'freightCarrier', 'invoiceLines']);
    }

    /**
     * @param  list<array{id?: int|null, kind: string, title: string, amount: int|string}>  $lines
     */
    public function syncInvoiceLines(Order $order, array $lines, User $actor): Order
    {
        $this->assertAdminEditable($order);

        if (! $order->isProforma()) {
            throw ValidationException::withMessages([
                'order' => 'ردیف‌های فاکتور فقط برای پیش‌فاکتور مجاز است.',
            ]);
        }

        $order->loadMissing('invoiceLines');
        $existingIds = $order->invoiceLines->pluck('id')->all();
        $keptIds = [];
        $changes = [];
        $sort = 0;

        foreach ($lines as $line) {
            $title = trim((string) ($line['title'] ?? ''));
            $kind = (string) ($line['kind'] ?? '');
            $amount = (int) ($line['amount'] ?? 0);
            $id = isset($line['id']) && $line['id'] !== '' ? (int) $line['id'] : null;

            if ($title === '' && $amount === 0) {
                continue;
            }

            if ($title === '') {
                throw ValidationException::withMessages([
                    'invoice_lines' => 'عنوان هر ردیف فاکتور الزامی است.',
                ]);
            }

            if (! in_array($kind, [OrderInvoiceLine::KIND_FEE, OrderInvoiceLine::KIND_ORDER_DISCOUNT], true)) {
                throw ValidationException::withMessages([
                    'invoice_lines' => 'نوع ردیف فاکتور معتبر نیست.',
                ]);
            }

            if ($amount < 1) {
                throw ValidationException::withMessages([
                    'invoice_lines' => 'مبلغ هر ردیف باید بزرگ‌تر از صفر باشد.',
                ]);
            }

            if ($id) {
                $model = $order->invoiceLines->firstWhere('id', $id);

                if ($model === null) {
                    continue;
                }

                $before = $model->only(['kind', 'title', 'amount']);
                $model->update([
                    'kind' => $kind,
                    'title' => $title,
                    'amount' => $amount,
                    'sort_order' => $sort,
                ]);
                $keptIds[] = $model->id;

                if ($before !== $model->only(['kind', 'title', 'amount'])) {
                    $changes[] = sprintf('ردیف «%s» به‌روزرسانی شد', $title);
                }
            } else {
                $model = $order->invoiceLines()->create([
                    'kind' => $kind,
                    'title' => $title,
                    'amount' => $amount,
                    'sort_order' => $sort,
                ]);
                $keptIds[] = $model->id;
                $changes[] = sprintf('ردیف «%s» افزوده شد (%s)', $title, $kind);
            }

            $sort++;
        }

        $deleteIds = array_diff($existingIds, $keptIds);

        if ($deleteIds !== []) {
            OrderInvoiceLine::query()->where('order_id', $order->id)->whereIn('id', $deleteIds)->delete();
            $changes[] = count($deleteIds).' ردیف حذف شد';
        }

        $this->draftOrders->recalculateTotals($order->fresh(['items', 'invoiceLines']));

        if ($changes !== []) {
            $this->orderLog->byUser(
                $order->fresh(),
                $actor,
                'ادمین ردیف‌های فاکتور پیش‌فاکتور را ویرایش کرد: '.implode('؛ ', $changes),
                'private',
                'proforma_admin_edit',
                ['invoice_line_changes' => $changes],
            );
        }

        return $order->fresh(['items', 'invoiceLines', 'user', 'representative', 'freightCarrier']);
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
