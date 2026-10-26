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

class ParsianPaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_parsian_failed_callback_marks_payment_canceled(): void
    {
        [$customer, $product, $address, $shipping] = $this->prepareCheckout();

        $order = $this->placeOnlineOrder($customer, $product, $address, $shipping);
        $tracking = 'PARSIANTRACK1';
        $token = 'pec-token-abc';

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => $order->final_amount,
            'gateway' => 'parsian',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => $tracking,
            'transaction_id' => $token,
        ]);

        $response = $this->post('/payment/callback/parsian?'.http_build_query(['payment' => $tracking]), [
            'Token' => $token,
            'status' => '1',
            'OrderId' => (string) $payment->id,
            'RRN' => '0',
            'Amount' => '100000',
        ]);

        $response->assertRedirect();
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
