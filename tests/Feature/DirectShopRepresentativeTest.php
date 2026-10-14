<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
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

class DirectShopRepresentativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_shop_checkout_assigns_direct_representative(): void
    {
        $directRep = User::factory()->create(['name' => 'چینی بازار']);
        config(['shop.checkout.direct_representative_id' => $directRep->id]);

        [$user, , $address, $shipping] = $this->prepareCheckout();

        Auth::login($user);
        app(CartService::class)->add(Product::query()->first()->id, 1);

        $order = app(CheckoutService::class)->placeOrder(
            $user,
            $address->id,
            $shipping->id,
            null,
            'online',
        );

        $this->assertSame($directRep->id, $order->representative_id);
        $this->assertFalse($order->isRepresentativeOrder());
        $this->assertTrue($order->isDirectShopRepresentativeAttribution());
        $this->assertSame('چینی بازار', $order->fresh(['representative'])->representativeLabel());
        $this->assertTrue($order->fresh()->isPendingShopOnlinePayment());
    }

    public function test_assign_command_backfills_null_representative_shop_orders(): void
    {
        $directRep = User::factory()->create(['name' => 'چینی بازار']);
        config(['shop.checkout.direct_representative_id' => $directRep->id]);

        $customer = User::factory()->create();
        $address = UserAddress::query()->create([
            'user_id' => $customer->id,
            'receiver_name' => 'تست',
            'receiver_phone' => '09121112233',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان',
            'postal_code' => '1234567890',
        ]);

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'address_id' => $address->id,
            'total_amount' => 1000,
            'final_amount' => 1000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'online',
            'status' => Order::STATUS_PENDING,
        ]);

        $this->artisan('shop:assign-direct-shop-representative')
            ->assertSuccessful();

        $this->assertSame($directRep->id, $order->fresh()->representative_id);
    }

    /**
     * @return array{0: User, 1: Product, 2: UserAddress, 3: ShippingMethod}
     */
    private function prepareCheckout(): array
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'ظروف', 'slug' => 'dishes', 'is_active' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'محصول تست',
            'slug' => 'test-product',
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'receiver_name' => 'تست',
            'receiver_phone' => '09121112233',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان',
            'postal_code' => '1234567890',
        ]);
        $shipping = ShippingMethod::query()->create([
            'name' => 'پست',
            'price' => 0,
            'is_active' => true,
        ]);

        return [$user, $product, $address, $shipping];
    }
}
