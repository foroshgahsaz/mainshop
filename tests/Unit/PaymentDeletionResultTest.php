<?php

namespace Tests\Unit;

use App\Services\Payment\PaymentDeletionResult;
use Tests\TestCase;

class PaymentDeletionResultTest extends TestCase
{
    public function test_it_describes_deleted_payments(): void
    {
        $result = new PaymentDeletionResult(deleted: 2);

        $this->assertSame('2 پرداخت حذف شد.', $result->message());
    }

    public function test_it_describes_skipped_successful_payments(): void
    {
        $result = new PaymentDeletionResult(skipped: 3);

        $this->assertSame('3 پرداخت موفق نادیده گرفته شد.', $result->message());
    }

    public function test_it_describes_mixed_results(): void
    {
        $result = new PaymentDeletionResult(deleted: 1, skipped: 2);

        $this->assertSame(
            '1 پرداخت حذف شد و 2 پرداخت موفق نادیده گرفته شد.',
            $result->message()
        );
    }
}
