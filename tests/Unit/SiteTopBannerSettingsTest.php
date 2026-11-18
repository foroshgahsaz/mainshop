<?php

namespace Tests\Unit;

use App\Services\Settings\SettingsService;
use Tests\TestCase;

class SiteTopBannerSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionClass(SettingsService::class);
        $prop = $ref->getProperty('siteMemo');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function test_site_top_banner_payload_when_enabled_with_image(): void
    {
        $service = new class extends SettingsService
        {
            public function all(): array
            {
                return [
                    'site' => [
                        'name' => 'تست',
                        'top_banner_enabled' => '1',
                        'top_banner_image' => 'settings/banners/promo.gif',
                        'top_banner_link' => 'https://example.com/sale',
                        'top_banner_alt' => 'جشنواره',
                    ],
                ];
            }
        };

        $banner = $service->siteTopBanner();

        $this->assertTrue($banner['enabled']);
        $this->assertSame('settings/banners/promo.gif', $banner['image']);
        $this->assertSame('https://example.com/sale', $banner['link']);
        $this->assertSame('جشنواره', $banner['alt']);
        $this->assertNotNull($banner['image_url']);
    }

    public function test_site_top_banner_reports_disabled_flag(): void
    {
        $service = new class extends SettingsService
        {
            public function all(): array
            {
                return [
                    'site' => [
                        'name' => 'تست',
                        'top_banner_enabled' => '0',
                        'top_banner_image' => 'settings/banners/promo.gif',
                    ],
                ];
            }
        };

        $banner = $service->siteTopBanner();

        $this->assertFalse($banner['enabled']);
    }
}