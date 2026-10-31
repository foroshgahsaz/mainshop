<?php

namespace Tests\Unit;

use App\Services\Payment\SadadSign;
use Tests\TestCase;

class SadadSignTest extends TestCase
{
    public function test_payment_sign_data_is_deterministic_base64(): void
    {
        $key = base64_encode(str_repeat('a', 24));
        $sign = SadadSign::paymentSignData('1234567', '99', 50000, $key);
        $again = SadadSign::paymentSignData('1234567', '99', 50000, $key);

        $this->assertSame($sign, $again);
        $this->assertNotSame('', $sign);

        $verifySign = SadadSign::verifySignData('sample-token', $key);
        $this->assertSame($verifySign, SadadSign::verifySignData('sample-token', $key));
    }
}
