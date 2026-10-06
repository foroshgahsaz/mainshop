<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\Payment\BajetReturnUrl;
use PHPUnit\Framework\TestCase;

class BajetReturnUrlTest extends TestCase
{
    public function test_builds_full_return_url_from_base_and_path(): void
    {
        $payment = new Payment(['tracking_code' => 'ABC123']);

        $url = BajetReturnUrl::forPayment($payment, [
            'return_url_base' => 'https://www.chinibazar.ir',
            'callback_url' => '/payment/callback/bajet',
        ]);

        $this->assertSame(
            'https://www.chinibazar.ir/payment/callback/bajet?payment=ABC123',
            $url,
        );
    }

    public function test_splits_full_callback_url_into_base_and_path(): void
    {
        $payment = new Payment(['tracking_code' => 'X1']);

        $url = BajetReturnUrl::forPayment($payment, [
            'return_url_base' => '',
            'callback_url' => 'https://www.chinibazar.ir/payment/callback/bajet',
        ]);

        $this->assertSame(
            'https://www.chinibazar.ir/payment/callback/bajet?payment=X1',
            $url,
        );
    }
}
