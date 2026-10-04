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
        $this->assertFalse($reader->isDownloadableBasename('other.log'));
        $this->assertTrue($reader->isDownloadableBasename('payments-2026-10-04.log'));
        $this->assertTrue($reader->isDownloadableBasename('payments-2026-10-04.log.zip'));
        $this->assertTrue($reader->isDownloadableBasename('payments.log'));
    }

    public function test_recent_entries_always_lists_twenty_days(): void
    {
        $entries = app(PaymentLogReader::class)->recentDayEntries(20);

        $this->assertCount(20, $entries);
        $this->assertSame(now()->format('Y-m-d'), $entries[0]['date']);
    }
}
