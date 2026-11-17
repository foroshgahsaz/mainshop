<?php

namespace Tests\Unit;

use App\Services\Settings\SettingsService;
use Tests\TestCase;

class NopaySettingsCredentialFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionClass(SettingsService::class);
        $p = $ref->getProperty('nopayMemo');
        $p->setAccessible(true);
        $p->setValue(null, null);
    }

    public function test_empty_nopay_plain_password_does_not_fall_through_to_toopi(): void
    {
        $service = new class extends SettingsService
        {
            /** @var array<string, array<string, string|null>> */
            public array $fakeAll = [];

            public function all(): array
            {
                return $this->fakeAll;
            }
        };

        $service->fakeAll = [
            'nopay' => [
                'service_password' => '',
                'service_password_encrypted' => 'support-encrypted-value',
            ],
            'toopi' => [
                'service_password' => 'C3456',
                'service_password_encrypted' => 'should-not-win',
            ],
        ];

        $nopay = $service->nopay();

        $this->assertSame('', $nopay['service_password']);
        $this->assertSame('support-encrypted-value', $nopay['service_password_encrypted']);
    }

    public function test_missing_nopay_password_keys_still_fall_back_to_toopi(): void
    {
        $service = new class extends SettingsService
        {
            /** @var array<string, array<string, string|null>> */
            public array $fakeAll = [];

            public function all(): array
            {
                return $this->fakeAll;
            }
        };

        $service->fakeAll = [
            'toopi' => [
                'service_password' => 'legacy-plain',
                'service_password_encrypted' => 'legacy-enc',
            ],
        ];

        $nopay = $service->nopay();

        $this->assertSame('legacy-plain', $nopay['service_password']);
        $this->assertSame('legacy-enc', $nopay['service_password_encrypted']);
    }
}
