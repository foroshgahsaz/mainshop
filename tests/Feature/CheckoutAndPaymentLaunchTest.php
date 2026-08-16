<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutAndPaymentLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_placing_online_order_reserves_stock(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 5);

        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 2);

        $this->assertTrue($order->stock_reserved);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
    }

    public function test_customer_cannot_use_another_users_address(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $stranger = User::factory()->create();
        $foreignAddress = UserAddress::query()->create([
            'user_id' => $stranger->id,
            'receiver_name' => 'Other',
            'receiver_phone' => '09123334455',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان دیگر',
        ]);

        Auth::login($user);
        app(CartService::class)->add($product->id, 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('آدرس انتخاب‌شده معتبر نیست.');

        app(CheckoutService::class)->placeOrder(
            $user,
            $foreignAddress->id,
            $shipping->id,
            null,
            'online'
        );
    }

    public function test_shipping_method_is_required_when_active_methods_exist(): void
    {
        [$user, $product, $address] = $this->prepareCheckout();
        Auth::login($user);
        app(CartService::class)->add($product->id, 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('روش ارسال را انتخاب کنید.');

        app(CheckoutService::class)->placeOrder($user, $address->id, null, null, 'online');
    }

    public function test_customer_can_cancel_unpaid_order_and_stock_is_restored(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 4);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 1);

        $this->assertSame(3, $product->fresh()->stock);

        $canceled = app(OrderService::class)->cancel($order, $user);

        $this->assertSame(Order::STATUS_CANCELED, $canceled->status);
        $this->assertFalse($canceled->stock_reserved);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_customer_cannot_cancel_paid_processing_order(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 2);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 1);
        $this->markOrderPaid($order);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('این سفارش قابل لغو نیست.');

        app(OrderService::class)->cancel($order->fresh(), $user);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_admin_can_cancel_paid_order_and_stock_is_restored_without_changing_payment(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 2);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 1);
        $payment = $this->markOrderPaid($order);
        $admin = User::factory()->admin()->create();

        $canceled = app(OrderService::class)->cancel($order->fresh(), $admin);

        $this->assertSame(Order::STATUS_CANCELED, $canceled->status);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertTrue(
            $canceled->notes()->where('event', 'manual_refund_required')->exists()
        );
    }

    public function test_expiring_unpaid_order_restores_stock(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 3);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 1);
        $order->forceFill(['created_at' => now()->subHours(2)])->save();

        $this->artisan('shop:expire-pending-orders', ['--minutes' => 60])
            ->assertSuccessful();

        $this->assertSame(Order::STATUS_CANCELED, $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_reserved);
    }

    public function test_successful_payment_verify_does_not_decrement_stock_again(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 5);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 2);
        $this->assertSame(3, $product->fresh()->stock);

        $payment = $this->initiateFakePayment($order);
        $verified = app(PaymentService::class)->verify($payment, $payment->transaction_id, 'OK');

        $this->assertSame(Payment::STATUS_SUCCESS, $verified->status);
        $this->assertSame(Order::STATUS_PROCESSING, $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_payment_verify_is_idempotent(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 2);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 1);
        $payment = $this->initiateFakePayment($order);

        $first = app(PaymentService::class)->verify($payment, $payment->transaction_id, 'OK');
        $second = app(PaymentService::class)->verify($payment->fresh(), $payment->transaction_id, 'OK');

        $this->assertSame(Payment::STATUS_SUCCESS, $first->status);
        $this->assertSame(Payment::STATUS_SUCCESS, $second->status);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(1, $order->payments()->where('status', Payment::STATUS_SUCCESS)->count());
    }

    public function test_zarinpal_already_verified_code_is_treated_as_success_once(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 2);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping, quantity: 1);
        $payment = $this->initiateFakePayment($order, verifyCode: 101);

        $verified = app(PaymentService::class)->verify($payment, $payment->transaction_id, 'OK');

        $this->assertSame(Payment::STATUS_SUCCESS, $verified->status);
        $this->assertSame(1, $product->fresh()->stock);
    }

    /**
     * @return array{0: User, 1: Product, 2: UserAddress, 3: ShippingMethod}
     */
    protected function prepareCheckout(int $stock = 5): array
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Product',
            'slug' => 'product-'.uniqid(),
            'price' => 100000,
            'stock' => $stock,
            'is_active' => true,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'receiver_name' => 'علی',
            'receiver_phone' => '09121112233',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان تست',
            'postal_code' => '1234567890',
        ]);
        $shipping = ShippingMethod::query()->create([
            'name' => 'پست پیشتاز',
            'price' => 25000,
            'is_active' => true,
        ]);

        return [$user, $product, $address, $shipping];
    }

    protected function placeOnlineOrder(
        User $user,
        Product $product,
        UserAddress $address,
        ShippingMethod $shipping,
        int $quantity = 1
    ): Order {
        Auth::login($user);
        app(CartService::class)->add($product->id, $quantity);

        return app(CheckoutService::class)->placeOrder(
            $user,
            $address->id,
            $shipping->id,
            null,
            'online'
        );
    }

    protected function initiateFakePayment(Order $order, int $verifyCode = 100): Payment
    {
        $authority = 'A'.str_repeat('1', 35);

        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['authority' => $authority, 'code' => 100],
                'errors' => [],
            ]),
            'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => ['code' => $verifyCode, 'card_pan' => '1234-56'],
                'errors' => [],
            ]),
        ]);

        $payment = app(PaymentService::class)->createForOrder($order);
        app(PaymentService::class)->initiate($payment, $order);

        return $payment->fresh();
    }

    protected function markOrderPaid(Order $order): Payment
    {
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $order->final_amount,
            'gateway' => 'zarinpal',
            'status' => Payment::STATUS_SUCCESS,
            'tracking_code' => 'PAY'.strtoupper(uniqid()),
            'paid_at' => now(),
        ]);

        $order->update(['status' => Order::STATUS_PROCESSING]);

        return $payment;
    }
}
