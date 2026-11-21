<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Services\Payment\NopayPaymentPageUrl;
use Tests\TestCase;

class NopayPaymentPageUrlTest extends TestCase
{
    public function test_build_matches_bmicc_query_format(): void
    {
        $url = NopayPaymentPageUrl::build(
            'https://op-n-cpg.bmicc.ir:44366',
            'efce8785-bdbf-4458-9215-d248ca041a10',
            'https://chinibazar.ir/payment/callback/nopay',
        );

        $this->assertStringStartsWith('https://op-n-cpg.bmicc.ir:44366/?t=', $url);
        $this->assertStringContainsString('rt=https%3A%2F%2Fchinibazar.ir%2Fpayment%2Fcallback%2Fnopay%3Ftv%3Defce8785', $url);
    }

    public function test_resolve_uses_override_when_set(): void
    {
        $payment = new Payment(['tracking_code' => 'T1']);

        $resolved = NopayPaymentPageUrl::resolve(
            'https://old-host/?t=x&rt=y',
            'token-1',
            [
                'callback_url' => '/payment/callback/nopay',
                'payment_page_base_url' => 'https://new-host:8443',
            ],
            $payment,
        );

        $this->assertStringStartsWith('https://new-host:8443/?t=', $resolved);
    }
}