<?php

namespace Tests\Unit;

use App\Models\Order;
use Carbon\Carbon;
use Tests\TestCase;

class RepresentativeProformaReservationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_active_reservation_when_until_is_future(): void
    {
        $order = new Order([
            'stock_reserved' => true,
            'stock_reserved_until' => now()->addHour(),
        ]);

        $this->assertTrue($order->hasActiveStockReservation());
        $this->assertFalse($order->isReservationExpired());
    }

    public function test_expired_reservation_when_until_is_past(): void
    {
        $order = new Order([
            'stock_reserved' => true,
            'stock_reserved_until' => now()->subMinute(),
        ]);

        $this->assertFalse($order->hasActiveStockReservation());
        $this->assertTrue($order->isReservationExpired());
    }
}
