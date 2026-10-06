<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\TaraInvoiceItemBuilder;
use PHPUnit\Framework\TestCase;

class TaraInvoiceItemBuilderTest extends TestCase
{
    public function test_builds_group_fields_from_config(): void
    {
        $order = new Order(['tracking_code' => 'ORD-1']);
        $payment = new Payment(['tracking_code' => 'PAY-1']);

        $item = (new TaraInvoiceItemBuilder)->build($order, $payment, [
            'default_group' => '15',
            'default_group_title' => 'خانه و آشپزخانه',
        ], 50000);

        $this->assertSame('15', $item['group']);
        $this->assertSame('خانه و آشپزخانه', $item['groupTitle']);
        $this->assertSame('PAY-1', $item['code']);
        $this->assertSame(50000, $item['fee']);
    }
}
