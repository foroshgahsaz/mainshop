<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Support\ShopLabels;
use Tests\TestCase;

class OrderWarehouseProcessingStatusTest extends TestCase
{
    public function test_warehouse_processing_has_persian_label(): void
    {
        $this->assertSame('پردازش انبار', ShopLabels::orderStatus(Order::STATUS_WAREHOUSE_PROCESSING));
    }

    public function test_can_advance_when_paid_and_processing(): void
    {
        $order = new Order([
            'status' => Order::STATUS_PROCESSING,
            'final_amount' => 100_000,
        ]);
        $order->setRelation('payments', collect([
            (object) ['status' => 'success', 'amount' => 100_000],
        ]));

        $this->assertTrue($order->canAdvanceToWarehouseProcessing());
    }

    public function test_cannot_advance_when_unpaid(): void
    {
        $order = new Order([
            'status' => Order::STATUS_PROCESSING,
            'final_amount' => 100_000,
        ]);
        $order->setRelation('payments', collect());

        $this->assertFalse($order->canAdvanceToWarehouseProcessing());
    }
}
