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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DigiPayPaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_digipay_cancel_callback_redirects_without_server_error(): void
    {
        [$customer, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($customer, $product, $address, $shipping);
        $tracking = 'DIGIPAYCNCL1';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => $order->final_amount,
            'gateway' => 'digipay',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => $tracking,
            'raw_response' => [
                'digipay_provider_id' => $tracking.'___1',
                'digipay_ticket_type' => 11,
            ],
        ]);

        $response = $this->get('/payment/callback/digipay?'.http_build_query([
            'payment' => $tracking,
        ]));

        $response->assertRedirect();
        $this->assertSame(Payment::STATUS_CANCELED, $payment->fresh()->status);
    }

    public function test_digipay_resolves_payment_by_provider_id_when_query_payment_missing(): void
    {
        [$customer, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($customer, $product, $address, $shipping);
        $tracking = 'DIGIPAYPRV1';
        $providerId = $tracking.'___1';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => $order->final_amount,
            'gateway' => 'digipay',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => $tracking,
            'raw_response' => [
                'digipay_provider_id' => $providerId,
                'digipay_ticket_type' => 11,
            ],
        ]);

        $response = $this->post('/payment/callback/digipay', [
            'providerId' => $providerId,
            'result' => 'FAILURE',
        ]);

        $response->assertRedirect();
        $this->assertSame(Payment::STATUS_CANCELED, $payment->fresh()->status);
    }

    public function test_digipay_success_callback_verifies_and_redirects(): void
    {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'refresh_token' => 'refresh',
            ]),
            '*/purchases/verify*' => Http::response([
                'result' => ['status' => 0, 'message' => 'OK'],
            ]),
            '*/purchases/deliver*' => Http::response([
                'result' => ['status' => 0, 'message' => 'OK'],
            ]),
        ]);

        app(\App\Services\Settings\SettingsService::class)->setMany('digipay', [
            'enabled' => true,
            'username' => 'u',
            'password' => 'p',
            'client_id' => 'id',
            'client_secret' => 'secret',
            'sandbox' => true,
            'amount_unit' => 'toman',
            'ticket_type' => 11,
            'access_token' => 'cached',
            'refresh_token' => 'cached-r',
        ]);

        [$customer, $product, $address, $shipping] = $this->prepareCheckout();
        $order = $this->placeOnlineOrder($customer, $product, $address, $shipping);
        $tracking = 'DIGIPAYSUC1';
        $providerId = $tracking.'___1';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => $order->final_amount,
            'gateway' => 'digipay',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => $tracking,
            'raw_response' => [
                'digipay_provider_id' => $providerId,
                'digipay_ticket_type' => 11,
            ],
        ]);

        $response = $this->post('/payment/callback/digipay?'.http_build_query([
            'payment' => $tracking,
        ]), [
            'providerId' => $providerId,
            'trackingCode' => '571000000098',
            'result' => 'SUCCESS',
            'amount' => $order->final_amount * 10,
            'type' => 13,
        ]);

        $response->assertRedirect();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->fresh()->status);
        $this->assertSame('571000000098', $payment->fresh()->transaction_id);
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
}