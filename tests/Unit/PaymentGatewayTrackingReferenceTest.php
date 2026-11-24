<?php

namespace Tests\Unit;

use App\Models\Payment;
use Tests\TestCase;

class PaymentGatewayTrackingReferenceTest extends TestCase
{
    public function test_digipay_reads_tracking_code_from_callback_payload(): void
    {
        $payment = new Payment([
            'gateway' => 'digipay',
            'tracking_code' => 'IXQEUHFDZPKG',
            'transaction_id' => null,
            'card_number' => null,
            'raw_response' => [
                'trackingCode' => '3982616221795521667250',
                'digipay_callback' => [
                    'trackingCode' => '3982616221795521667250',
                ],
            ],
        ]);

        $this->assertSame('3982616221795521667250', $payment->gatewayTrackingReference());
    }

    public function test_digipay_falls_back_to_card_number_after_success_verify(): void
    {
        $payment = new Payment([
            'gateway' => 'digipay',
            'tracking_code' => 'SHOPCODE',
            'card_number' => '3982616221795521667250',
            'raw_response' => [],
        ]);

        $this->assertSame('3982616221795521667250', $payment->gatewayTrackingReference());
    }
}