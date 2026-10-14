<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\ShopLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_payment_method_label_uses_latest_payment_gateway_for_legacy_online(): void
    {
        $order = $this->makeOrder(['payment_method' => 'online']);

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => $order->final_amount,
            'gateway' => 'bajet',
            'status' => Payment::STATUS_CANCELED,
            'tracking_code' => 'PAY1',
        ]);

        $this->assertSame('باجت‌پی', ShopLabels::orderPaymentMethodLabel($order->fresh()->load('payments')));
    }

    public function test_order_payment_method_label_uses_stored_gateway_slug(): void
    {
        $order = $this->makeOrder(['payment_method' => 'bajet']);

        $this->assertSame('باجت‌پی', ShopLabels::orderPaymentMethodLabel($order));
    }

    public function test_order_payment_method_label_for_cod(): void
    {
        $order = $this->makeOrder(['payment_method' => 'cod']);

        $this->assertSame('پرداخت در محل', ShopLabels::orderPaymentMethodLabel($order));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeOrder(array $overrides = []): Order
    {
        $user = User::factory()->create();

        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'receiver_name' => 'تست',
            'receiver_phone' => '09121112233',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان',
            'postal_code' => '1234567890',
        ]);

        return Order::query()->create(array_merge([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'total_amount' => 1000,
            'final_amount' => 1000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'online',
            'status' => Order::STATUS_PENDING,
        ], $overrides));
    }
}
