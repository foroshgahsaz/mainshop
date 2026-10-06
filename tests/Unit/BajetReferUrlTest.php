<?php

namespace Tests\Unit;

use App\Services\Payment\BajetReferUrl;
use PHPUnit\Framework\TestCase;

class BajetReferUrlTest extends TestCase
{
    public function test_upgrades_http_on_port_8443_to_https(): void
    {
        $raw = 'http://sandbox-jetpay.simotechtest.ir:8443/fa/ep/invoice?requestId=abc';

        $this->assertSame(
            'https://sandbox-jetpay.simotechtest.ir:8443/fa/ep/invoice?requestId=abc',
            BajetReferUrl::normalizeScheme($raw),
        );
    }

    public function test_rebuilds_url_when_portal_base_configured(): void
    {
        $config = [
            'sandbox' => true,
            'portal_sandbox_base_url' => 'https://public-jetpay.test',
        ];

        $this->assertSame(
            'https://public-jetpay.test/fa/ep/invoice?requestId=ref-1',
            BajetReferUrl::resolve('http://internal:8443/fa/ep/invoice?requestId=ignored', 'ref-1', $config),
        );
    }
}
