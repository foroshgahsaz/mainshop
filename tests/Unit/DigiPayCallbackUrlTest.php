<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\Payment\DigiPayCallbackUrl;
use Tests\TestCase;

class DigiPayCallbackUrlTest extends TestCase
{
    public function test_builds_absolute_callback_with_payment_query(): void
    {
        config(['app.url' => 'https://www.chinibazar.ir']);

        $payment = new Payment([
            'tracking_code' => '2U2NF61HNEWU',
        ]);

        $url = DigiPayCallbackUrl::forPayment($payment, [
            'callback_url' => '/payment/callback/digipay',
        ]);

        $this->assertSame(
            'https://www.chinibazar.ir/payment/callback/digipay?payment=2U2NF61HNEWU',
            $url
        );
    }

    public function test_strips_host_from_misconfigured_callback(): void
    {
        config(['app.url' => 'https://chinibazar.ir']);

        $payment = new Payment(['tracking_code' => 'ABC']);

        $url = DigiPayCallbackUrl::forPayment($payment, [
            'callback_url' => 'https://chinibazar.ir/payment/callback/digipay',
        ]);

        $this->assertSame(
            'https://chinibazar.ir/payment/callback/digipay?payment=ABC',
            $url
        );
    }
}