<?php

namespace Tests\Unit;

use App\Services\Order\OrderDeletionResult;
use PHPUnit\Framework\TestCase;

class OrderDeletionResultTest extends TestCase
{
    public function test_message_summarizes_deleted_and_skipped_counts(): void
    {
        $result = new OrderDeletionResult(deleted: 2, skipped: 1);

        $this->assertSame('2 سفارش حذف شد و 1 سفارش با پرداخت موفق نادیده گرفته شد.', $result->message());
    }
}
