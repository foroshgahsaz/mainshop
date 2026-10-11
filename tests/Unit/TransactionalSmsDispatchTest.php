<?php

namespace Tests\Unit;

use App\Jobs\SendTransactionalSmsJob;
use Tests\TestCase;

class TransactionalSmsDispatchTest extends TestCase
{
    public function test_dispatch_after_response_does_not_throw_when_queuing_sms(): void
    {
        SendTransactionalSmsJob::dispatchAfterResponse('proforma_created', '09121234567', [
            'site_name' => 'Test',
        ]);

        $this->addToAssertionCount(1);
    }
}
