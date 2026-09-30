<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepresentativeDraftOrderService
{
    public function createDraft(User $representative, User $customer): Order
    {
        $this->assertRepOwnsCustomer($representative, $customer);

        $address = $customer->addresses()
            ->where('is_default', true)
            ->first() ?? $customer->addresses()->first();

        if ($address === null) {
            throw ValidationException::withMessages([
                'customer_id' => 'برای این مشتری آدرس ثبت نشده است.',
            ]);
        }

        return Order::query()->create([
            'user_id' => $customer->id,
            'representative_id' => $representative->id,
            'address_id' => $address->id,
            'total_amount' => 0,
            'final_amount' => 0,
            'shipping_amount' => $this->defaultShippingAmount(),
            'discount_amount' => 0,
            'payment_method' => 'online',
            'status' => Order::STATUS_DRAFT,
            'tracking_code' => $this->generateTrackingCode(),
            'shipping_method_id' => $this->defaultShippingMethodId(),
        ]);
    }

    public function syncCatalogFilters(Order $order, array $filters): void
    {
        $this->assertDraftOwnedBy($order, auth()->user());

        $order->update([
            'catalog_filters' => [
                'family_id' => $filters['family_id'] ?? null,
                'plant_id' => $filters['plant_id'] ?? null,
                'brand_id' => $filters['brand_id'] ?? null,
                'template_id' => $filters['template_id'] ?? null,
            ],
        ]);
    }

    public function addProduct(Order $order, Product $product, int $quantity = 1): OrderItem
    {
        $this->assertDraftOwnedBy($order, auth()->user());

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'تعداد باید حداقل ۱ باشد.',
            ]);
        }

        $unitPrice = $product->effective_price;

        $existing = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing !== null) {
            $newQuantity = $existing->quantity + $quantity;

            $existing->update([
                'quantity' => $newQuantity,
                'price' => $unitPrice,
                'total_price' => $unitPrice * $newQuantity,
                'product_name' => $product->name,
                'sku' => $product->sku,
            ]);

            $this->recalculateTotals($order);

            return $existing->refresh();
        }

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'product_name' => $product->name,
            'sku' => $product->sku,
        ]);

        $this->recalculateTotals($order);

        return $item;
    }

    public function removeItem(Order $order, int $orderItemId): void
    {
        $this->assertDraftOwnedBy($order, auth()->user());

        OrderItem::query()
            ->where('order_id', $order->id)
            ->whereKey($orderItemId)
            ->delete();

        $this->recalculateTotals($order);
    }

    public function updateItemQuantity(Order $order, int $orderItemId, int $quantity): void
    {
        $this->assertDraftOwnedBy($order, auth()->user());

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'تعداد باید حداقل ۱ باشد.',
            ]);
        }

        if ($quantity > 999) {
            throw ValidationException::withMessages([
                'quantity' => 'تعداد نباید بیشتر از ۹۹۹ باشد.',
            ]);
        }

        $item = OrderItem::query()
            ->where('order_id', $order->id)
            ->whereKey($orderItemId)
            ->firstOrFail();

        $unitPrice = (int) $item->price;

        $item->update([
            'quantity' => $quantity,
            'total_price' => $unitPrice * $quantity,
        ]);

        $this->recalculateTotals($order);
    }

    public function submitProforma(Order $order): Order
    {
        $this->assertDraftOwnedBy($order, auth()->user());

        if (! $order->items()->exists()) {
            throw ValidationException::withMessages([
                'order' => 'حداقل یک محصول به پیش‌فاکتور اضافه کنید.',
            ]);
        }

        $this->recalculateTotals($order);

        $order->update([
            'status' => Order::STATUS_PROFORMA,
        ]);

        return $order->fresh(['items', 'user', 'shippingMethod']);
    }

    public function recalculateTotals(Order $order): void
    {
        if (! $order->isDraft() && ! $order->isProforma()) {
            return;
        }

        $this->consolidateDuplicateItems($order);

        $order->loadMissing('items');

        $itemsTotal = (int) $order->items->sum('total_price');
        $shipping = (int) $order->shipping_amount;

        $order->update([
            'total_amount' => $itemsTotal,
            'discount_amount' => 0,
            'final_amount' => $itemsTotal + $shipping,
        ]);
    }

    public function assertDraftOwnedBy(Order $order, ?User $representative): void
    {
        if ($representative === null || $order->representative_id !== $representative->id || ! $order->isDraft()) {
            throw ValidationException::withMessages([
                'order' => 'دسترسی به این پیش‌سفارش مجاز نیست.',
            ]);
        }
    }

    public function assertRepCanView(Order $order, ?User $representative): void
    {
        if (
            $representative === null
            || $order->representative_id !== $representative->id
            || (! $order->isDraft() && ! $order->isProforma())
        ) {
            throw ValidationException::withMessages([
                'order' => 'دسترسی به این پیش‌فاکتور مجاز نیست.',
            ]);
        }
    }

    private function assertRepOwnsCustomer(User $representative, User $customer): void
    {
        if ($customer->created_by_representative_id !== $representative->id) {
            throw ValidationException::withMessages([
                'customer_id' => 'این مشتری متعلق به نمایندگی شما نیست.',
            ]);
        }
    }

    private function defaultShippingAmount(): int
    {
        $configured = (int) config('shop.representative.default_shipping_amount', 0);

        if ($configured > 0) {
            return $configured;
        }

        return (int) (ShippingMethod::query()->where('is_active', true)->orderBy('price')->value('price') ?? 0);
    }

    private function defaultShippingMethodId(): ?int
    {
        return ShippingMethod::query()->where('is_active', true)->orderBy('price')->value('id');
    }

    private function generateTrackingCode(): string
    {
        return 'DR-'.strtoupper(Str::random(10));
    }

    /**
     * Merge legacy duplicate lines (same product) into one row per product.
     */
    private function consolidateDuplicateItems(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items->groupBy('product_id') as $items) {
            if ($items->count() <= 1) {
                continue;
            }

            $keep = $items->first();
            $totalQuantity = (int) $items->sum('quantity');
            $totalPrice = (int) $items->sum('total_price');

            $keep->update([
                'quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'price' => $totalQuantity > 0 ? (int) round($totalPrice / $totalQuantity) : (int) $keep->price,
            ]);

            OrderItem::query()
                ->where('order_id', $order->id)
                ->where('product_id', $keep->product_id)
                ->whereKeyNot($keep->id)
                ->delete();
        }

        $order->unsetRelation('items');
    }
}
