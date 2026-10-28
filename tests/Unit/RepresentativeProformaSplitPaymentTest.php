<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Representative\RepresentativeProformaSplitPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepresentativeProformaSplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_settlement_requires_both_credit_and_cash_legs(): void
    {
        $customer = User::factory()->create();
        $rep = User::factory()->create();

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'representative_id' => $rep->id,
            'final_amount' => 50000000,
            'total_amount' => 50000000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'tara',
            'proforma_planned_credit_amount' => 30000000,
            'proforma_cash_gateway' => 'parsian',
            'status' => Order::STATUS_PROFORMA,
            'stock_reserved' => true,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'paid_by_representative_id' => $rep->id,
            'amount' => 20000000,
            'gateway' => 'parsian',
            'status' => Payment::STATUS_SUCCESS,
            'tracking_code' => 'CASH0001',
        ]);

        $split = app(RepresentativeProformaSplitPayment::class);

        $this->assertTrue($split->usesSplitPlan($order));
        $this->assertSame(20000000, $split->paidCashAmount($order));
        $this->assertSame(0, $split->paidCreditAmount($order));
        $this->assertFalse($split->isSettlementComplete($order));
        $this->assertFalse($order->fresh(['payments'])->isPaid());

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'paid_by_representative_id' => $rep->id,
            'amount' => 30000000,
            'gateway' => 'tara',
            'status' => Payment::STATUS_SUCCESS,
            'tracking_code' => 'CRED0001',
        ]);

        $order = $order->fresh(['payments']);
        $this->assertTrue($split->isSettlementComplete($order));
        $this->assertTrue($order->isPaid());
    }
}
