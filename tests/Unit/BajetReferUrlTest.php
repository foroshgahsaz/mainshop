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

    public function test_swaps_origin_only_when_portal_base_configured(): void
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

    public function test_production_keeps_login_path_from_api(): void
    {
        $config = [
            'sandbox' => false,
            'portal_base_url' => 'https://jetpay.mybajet.ir',
        ];

        $raw = 'https://jetpay.mybajet.ir/fa/ep/login?id=6ac4c01f04cd4b486eac7c81';

        $this->assertSame(
            $raw,
            BajetReferUrl::resolve($raw, '6ac4c01f04cd4b486eac7c81', $config),
        );
    }

    public function test_without_portal_uses_api_url_normalized(): void
    {
        $config = ['sandbox' => true, 'portal_sandbox_base_url' => ''];

        $this->assertSame(
            'https://jetpay.mybajet.ir/fa/ep/login?id=abc',
            BajetReferUrl::resolve('http://jetpay.mybajet.ir:443/fa/ep/login?id=abc', 'abc', $config),
        );
    }
}
