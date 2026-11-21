<?php

namespace Tests\Unit;

use App\Services\Payment\NopayReturnUrl;
use Tests\TestCase;

class NopayReturnUrlTest extends TestCase
{
    public function test_absolute_return_url_has_no_query_string(): void
    {
        $url = NopayReturnUrl::absolute(['callback_url' => '/payment/callback/nopay'], new \App\Models\Payment);

        $this->assertStringNotContainsString('?payment=', $url);
        $this->assertStringEndsWith('/payment/callback/nopay', $url);
    }

    public function test_strips_query_from_configured_callback_path(): void
    {
        $path = NopayReturnUrl::path([
            'callback_url' => '/payment/callback/nopay?payment=OLD',
        ]);

        $this->assertSame('/payment/callback/nopay', $path);
    }
}