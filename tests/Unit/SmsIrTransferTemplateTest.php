<?php

namespace Tests\Unit;

use Tests\TestCase;

class SmsIrTransferTemplateTest extends TestCase
{
    public function test_default_transfer_template_id_is_785592(): void
    {
        $this->assertSame(785592, (int) config('sms.smsir.transfer_template_id'));
    }
}
