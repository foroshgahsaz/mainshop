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
use App\Services\Payment\TaraRefundService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaraSplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->enableTara();
    }

    public function test_tara_full_payment_moves_order_to_processing(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);

        $payment = $this->initiateTaraPayment($order, capturedToman: $order->final_amount);
        $verified = app(PaymentService::class)->verify($payment, $payment->transaction_id, '0');

        $this->assertSame(Payment::STATUS_SUCCESS, $verified->status);
        $this->assertSame($order->final_amount, $verified->amount);
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(Order::STATUS_PROCESSING, $order->fresh()->status);
        $this->assertSame(0, $order->fresh()->remainingAmount());
    }

    public function test_partial_tara_keeps_order_pending_until_cash_remainder_is_paid(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);
        $credit = 100000;
        $this->assertGreaterThan($credit, $order->final_amount);

        $taraPayment = $this->initiateTaraPayment($order, capturedToman: $credit);
        $verifiedTara = app(PaymentService::class)->verify($taraPayment, $taraPayment->transaction_id, '0');

        $order = $order->fresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $verifiedTara->status);
        $this->assertSame($credit, $verifiedTara->amount);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertFalse($order->isPaid());
        $this->assertTrue($order->canPayAgain());
        $this->assertSame($order->final_amount - $credit, $order->remainingAmount());

        $cashPayment = $this->initiateZarinpalPayment($order);
        $this->assertSame($order->remainingAmount(), $cashPayment->amount);

        $verifiedCash = app(PaymentService::class)->verify($cashPayment, $cashPayment->transaction_id, 'OK');

        $order = $order->fresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $verifiedCash->status);
        $this->assertTrue($order->isPaid());
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(0, $order->remainingAmount());
        $this->assertFalse($order->canPayAgain());
    }

    public function test_customer_cannot_cancel_after_partial_tara_payment(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout(stock: 3);
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);
        $this->initiateTaraPayment($order, capturedToman: 100000);
        $payment = $order->payments()->latest('id')->first();
        app(PaymentService::class)->verify($payment, $payment->transaction_id, '0');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('این سفارش قابل لغو نیست.');

        app(OrderService::class)->cancel($order->fresh(), $user);
    }

    public function test_partially_paid_order_is_not_expired(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);
        $this->initiateTaraPayment($order, capturedToman: 100000);
        $payment = $order->payments()->latest('id')->first();
        app(PaymentService::class)->verify($payment, $payment->transaction_id, '0');
        $order->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->artisan('shop:expire-pending-orders', ['--minutes' => 60])->assertSuccessful();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertTrue($order->fresh()->stock_reserved);
        $this->assertTrue($order->fresh()->canPayAgain());
    }

    public function test_tara_callback_posts_result_and_redirects_to_order(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);
        $payment = $this->initiateTaraPayment($order, capturedToman: $order->final_amount);

        $response = $this->post('/payment/callback/tara', [
            'payment' => $payment->tracking_code,
            'result' => '0',
            'token' => $payment->transaction_id,
            'orderId' => $payment->tracking_code,
        ]);

        $response->assertRedirect(route('account.orders.show', $order));
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PROCESSING, $order->fresh()->status);
    }

    public function test_tara_redirect_page_contains_purchase_form(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);
        $payment = $this->initiateTaraPayment($order, capturedToman: $order->final_amount);

        $this->get(route('payment.tara.redirect', $payment->tracking_code))
            ->assertOk()
            ->assertSee('api/ipgPurchase', false)
            ->assertSee($payment->transaction_id, false)
            ->assertSee('tara_ipg', false);
    }

    public function test_tara_refund_marks_payment_refunded_and_reopens_order(): void
    {
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);
        $payment = $this->initiateTaraPayment($order, capturedToman: $order->final_amount);
        app(PaymentService::class)->verify($payment, $payment->transaction_id, '0');

        Http::fake([
            'stage.tara-club.ir/*' => function ($request) {
                if (str_contains($request->url(), 'login/refund')) {
                    return Http::response([
                        'success' => true,
                        'accessCode' => 'refund-token',
                    ]);
                }

                return Http::response([
                    'success' => true,
                    'data' => 'refunded',
                ]);
            },
        ]);

        $refunded = app(TaraRefundService::class)->refund($payment->fresh());

        $this->assertSame(Payment::STATUS_REFUNDED, $refunded->status);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertTrue($order->fresh()->canPayAgain());
    }

    public function test_disabled_tara_cannot_be_used(): void
    {
        app(SettingsService::class)->set('tara', 'enabled', false);
        [$user, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($user, $product, $address, $shipping);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('این درگاه پرداخت فعال نیست.');

        app(PaymentService::class)->createForOrder($order, 'tara');
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

    protected function enableTara(): void
    {
        app(SettingsService::class)->setMany('tara', [
            'enabled' => true,
            'sandbox' => true,
            'username' => 'tara_ipg',
            'password' => 'tara_ipg@123_$',
            'service_id' => '101',
            'amount_unit' => 'toman',
            'callback_url' => '/payment/callback/tara',
            'client_ip' => '127.0.0.1',
        ]);
    }

    protected function initiateTaraPayment(Order $order, int $capturedToman): Payment
    {
        $rial = $capturedToman * 10;

        Http::fake([
            'stage-pay.tara360.ir/*' => function ($request) use ($rial) {
                $url = $request->url();

                if (str_contains($url, 'authenticate')) {
                    return Http::response([
                        'accessToken' => 'acc-token',
                        'result' => '0',
                        'expireTime' => time() + 3600,
                    ]);
                }

                if (str_contains($url, 'getToken')) {
                    return Http::response([
                        'token' => 'tara-token-1',
                        'result' => '0',
                    ]);
                }

                if (str_contains($url, 'purchaseVerify') || str_contains($url, 'purchaseInquiry')) {
                    return Http::response([
                        'token' => 'tara-token-1',
                        'result' => '0',
                        'amount' => (string) $rial,
                        'rrn' => 'RRN-TARA-1',
                    ]);
                }

                return Http::response(['result' => '15', 'description' => 'unfaked '.$url], 404);
            },
        ]);

        $payment = app(PaymentService::class)->createForOrder($order, 'tara');
        app(PaymentService::class)->initiate($payment, $order);

        return $payment->fresh();
    }

    protected function initiateZarinpalPayment(Order $order): Payment
    {
        $authority = 'A'.str_repeat('1', 35);

        Http::fake([
            'https://sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['authority' => $authority, 'code' => 100],
                'errors' => [],
            ]),
            'https://sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => ['code' => 100, 'card_pan' => '1234-56'],
                'errors' => [],
            ]),
        ]);

        $payment = app(PaymentService::class)->createForOrder($order, 'zarinpal');
        app(PaymentService::class)->initiate($payment, $order);

        return $payment->fresh();
    }
}
