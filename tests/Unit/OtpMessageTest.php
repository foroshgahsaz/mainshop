<?php

namespace Tests\Unit;

use App\Models\OtpMessage;
use Tests\TestCase;

class OtpMessageTest extends TestCase
{
    public function test_display_name_uses_snapshot_then_dash(): void
    {
        $withName = new OtpMessage(['user_name' => 'علی رضایی']);
        $this->assertSame('علی رضایی', $withName->displayName());

        $empty = new OtpMessage(['user_name' => null]);
        $this->assertSame('—', $empty->displayName());
    }
}
