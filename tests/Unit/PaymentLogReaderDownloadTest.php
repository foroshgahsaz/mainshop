<?php

namespace Tests\Unit;

use App\Services\Payment\PaymentLogReader;
use Tests\TestCase;

class PaymentLogReaderDownloadTest extends TestCase
{
    public function test_it_rejects_non_payment_log_basenames(): void
    {
        $reader = app(PaymentLogReader::class);

        $this->assertNull($reader->resolvePathByBasename('laravel.log'));
        $this->assertNull($reader->resolvePathByBasename('payments-evil.txt'));
        $this->assertFalse($reader->isPaymentLogBasename('other.log'));
        $this->assertTrue($reader->isPaymentLogBasename('payments-2026-10-04.log'));
        $this->assertTrue($reader->isPaymentLogBasename('payments.log'));
    }
}
