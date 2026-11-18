<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Representative\RepresentativeProformaPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class RepresentativeProformaDarikPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_attach_darik_member_code_writes_payment_raw_response(): void
    {
        $customer = User::factory()->create(['darik_member_code' => '5892251560796087']);
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'representative_id' => User::factory()->create()->id,
            'final_amount' => 50000,
            'total_amount' => 50000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'darik',
            'status' => Order::STATUS_PROFORMA,
            'stock_reserved' => true,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => 50000,
            'gateway' => 'darik',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'DARIKTEST1',
        ]);

        $service = app(RepresentativeProformaPaymentService::class);
        $method = new ReflectionMethod($service, 'attachDarikMemberCodeIfNeeded');
        $method->setAccessible(true);
        $method->invoke($service, $payment, $order->fresh('user'));

        $this->assertSame('5892251560796087', data_get($payment->fresh()->raw_response, 'darik.member_code'));
    }

    public function test_attach_darik_member_code_throws_when_customer_has_no_code(): void
    {
        $customer = User::factory()->create(['darik_member_code' => null]);
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'representative_id' => User::factory()->create()->id,
            'final_amount' => 50000,
            'total_amount' => 50000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'darik',
            'status' => Order::STATUS_PROFORMA,
            'stock_reserved' => true,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'amount' => 50000,
            'gateway' => 'darik',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'DARIKTEST2',
        ]);

        $service = app(RepresentativeProformaPaymentService::class);
        $method = new ReflectionMethod($service, 'attachDarikMemberCodeIfNeeded');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('شناسه ۱۶ رقمی کیف پول داریک');

        $method->invoke($service, $payment, $order->fresh('user'));
    }
}