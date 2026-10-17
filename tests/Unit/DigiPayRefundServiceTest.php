<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\Payment\DigiPayRefundService;
use Tests\TestCase;

class DigiPayRefundServiceTest extends TestCase
{
    public function test_sale_tracking_code_prefers_callback_tracking_code(): void
    {
        $payment = new Payment([
            'gateway' => 'digipay',
            'transaction_id' => '',
            'card_number' => '111',
            'raw_response' => ['trackingCode' => '571000000098'],
        ]);

        $service = app(DigiPayRefundService::class);
        $method = new \ReflectionMethod(DigiPayRefundService::class, 'saleTrackingCode');
        $method->setAccessible(true);

        $this->assertSame('571000000098', $method->invoke($service, $payment));
    }

    public function test_ticket_type_uses_digipay_type_from_raw_response(): void
    {
        $payment = new Payment([
            'gateway' => 'digipay',
            'raw_response' => ['digipay_type' => '13'],
        ]);

        $service = app(DigiPayRefundService::class);
        $method = new \ReflectionMethod(DigiPayRefundService::class, 'ticketType');
        $method->setAccessible(true);

        $this->assertSame('13', $method->invoke($service, $payment));
    }
}
