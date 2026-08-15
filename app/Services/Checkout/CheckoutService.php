<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Services\Cart\CartService;
use App\Services\Cart\StockService;
use App\Services\Order\OrderActivityLogger;
use App\Services\Sms\OrderSmsNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected StockService $stockService,
        protected CouponService $couponService,
        protected OrderActivityLogger $orderLog,
        protected OrderSmsNotifier $sms,
    ) {}

    public function preview(User $user, ?string $couponCode = null, ?int $shippingMethodId = null): array
    {
        $items = $this->cartService->getItems();

        if ($items->isEmpty()) {
            throw new RuntimeException('سبد خرید خالی است.');
        }

        $subtotal = $this->cartService->subtotal();
        $coupon = $couponCode ? $this->couponService->findValid($couponCode, $user, $subtotal) : null;
        $discount = $coupon ? $coupon->calculateDiscount($subtotal) : 0;
        $shippingMethod = $shippingMethodId
            ? ShippingMethod::where('is_active', true)->findOrFail($shippingMethodId)
            : null;
        $shipping = $shippingMethod ? $shippingMethod->calculateCost($subtotal - $discount) : 0;
        $total = max(0, $subtotal - $discount + $shipping);

        return compact('items', 'subtotal', 'coupon', 'discount', 'shippingMethod', 'shipping', 'total');
    }

    public function placeOrder(
        User $user,
        int $addressId,
        ?int $shippingMethodId,
        ?string $couponCode = null,
        string $paymentMethod = 'online',
        ?string $note = null
    ): Order {
        $preview = $this->preview($user, $couponCode, $shippingMethodId);

        $address = UserAddress::where('user_id', $user->id)->findOrFail($addressId);

        return DB::transaction(function () use ($user, $address, $preview, $paymentMethod, $note) {
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'coupon_id' => $preview['coupon']?->id,
                'shipping_method_id' => $preview['shippingMethod']?->id,
                'total_amount' => $preview['subtotal'],
                'discount_amount' => $preview['discount'],
                'shipping_amount' => $preview['shipping'],
                'final_amount' => $preview['total'],
                'payment_method' => $paymentMethod,
                'status' => Order::STATUS_PENDING,
                'tracking_code' => strtoupper(Str::random(10)),
                'note' => $note,
            ]);

            foreach ($preview['items'] as $item) {
                $product = \App\Models\Product::findOrFail($item['product_id']);
                $variant = isset($item['product_variant_id'])
                    ? \App\Models\ProductVariant::find($item['product_variant_id'])
                    : null;

                $this->stockService->assertAvailable($product, $variant, $item['quantity']);

                if ($paymentMethod === 'cod') {
                    $this->stockService->decrement($product, $variant, $item['quantity']);
                }

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total_price' => $item['price'] * $item['quantity'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'] ?? null,
                ]);
            }

            if ($preview['coupon']) {
                $this->couponService->recordUsage($preview['coupon'], $user, $order);
            }

            $this->cartService->clear();

            $this->orderLog->orderCreated($order, $user);

            if ($paymentMethod === 'cod') {
                $user->notify(new OrderConfirmedNotification($order));
                $this->sms->orderPlaced($order);
            } else {
                $user->notify(new OrderPlacedNotification($order));
                $this->sms->orderPlaced($order);
            }

            return $order->load(['items', 'address', 'shippingMethod', 'coupon']);
        });
    }
}
