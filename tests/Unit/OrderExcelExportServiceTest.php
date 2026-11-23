<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderExcelExportService;
use Tests\TestCase;

class OrderExcelExportServiceTest extends TestCase
{
    public function test_row_values_include_customer_phone(): void
    {
        $customer = new User([
            'name' => 'علی رضایی',
            'phone' => '09121234567',
        ]);

        $order = new Order([
            'tracking_code' => 'ORD-EXCEL-1',
            'final_amount' => 2_500_000,
            'payment_method' => 'cod',
            'status' => Order::STATUS_PENDING,
        ]);
        $order->setRelation('user', $customer);
        $order->setRelation('payments', collect());
        $order->created_at = now();

        $row = (new OrderExcelExportService)->rowValues($order);

        $this->assertSame('ORD-EXCEL-1', $row[0]);
        $this->assertSame('علی رضایی', $row[2]);
        $this->assertSame('09121234567', $row[3]);
        $this->assertSame(2_500_000, $row[5]);
    }

    public function test_header_includes_mobile_column(): void
    {
        $headers = (new OrderExcelExportService)->headerLabels();

        $this->assertContains('موبایل مشتری', $headers);
    }
}