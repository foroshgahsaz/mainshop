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
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BajetPaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_bajet_cancel_callback_redirects_representative_payment_result_without_server_error(): void
    {
        [$customer, $product, $address, $shipping] = $this->prepareCheckout();
        $representative = User::factory()->create([
            'is_representative' => true,
            'status' => true,
        ]);

        $order = $this->placeOnlineOrder($customer, $product, $address, $shipping);
        $order->update(['representative_id' => $representative->id]);

        $tracking = 'BHMUTIH4PHSU';
        $referenceId = '6ac66312eb9a05e777915015';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'paid_by_representative_id' => $representative->id,
            'amount' => $order->final_amount,
            'gateway' => 'bajet',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => $tracking,
            'transaction_id' => $referenceId,
        ]);

        $response = $this->get('/payment/callback/bajet?'.http_build_query([
            'payment' => $tracking,
            'id' => $referenceId,
            'orderId' => $tracking,
            'status' => 'false',
        ]));

        $response->assertRedirect();
        $target = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/representative/payment/result/', $target);
        $this->assertStringContainsString('signature=', $target);

        $this->get($target)->assertOk();

        $this->assertSame(Payment::STATUS_CANCELED, $payment->fresh()->status);
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
