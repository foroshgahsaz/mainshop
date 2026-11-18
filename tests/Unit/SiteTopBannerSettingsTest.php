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

    public function test_desktop_and_mobile_banner_payload(): void
    {
        $service = new class extends SettingsService
        {
            public function all(): array
            {
                return [
                    'site' => [
                        'name' => 'تست',
                        'top_banner_enabled' => '1',
                        'top_banner_image_desktop' => 'settings/banners/desktop.gif',
                        'top_banner_image_mobile' => 'settings/banners/mobile.gif',
                        'top_banner_link' => 'https://example.com/d',
                        'top_banner_link_mobile' => 'https://example.com/m',
                        'top_banner_alt' => 'دسکتاپ',
                        'top_banner_alt_mobile' => 'موبایل',
                    ],
                ];
            }
        };

        $banner = $service->siteTopBanner();

        $this->assertTrue($banner['enabled']);
        $this->assertTrue($banner['show_desktop']);
        $this->assertTrue($banner['show_mobile']);
        $this->assertSame('https://example.com/d', $banner['desktop']['link']);
        $this->assertSame('https://example.com/m', $banner['mobile']['link']);
        $this->assertSame('موبایل', $banner['mobile']['alt']);
    }

    public function test_mobile_falls_back_to_desktop_image_and_link(): void
    {
        $service = new class extends SettingsService
        {
            public function all(): array
            {
                return [
                    'site' => [
                        'name' => 'تست',
                        'top_banner_enabled' => '1',
                        'top_banner_image_desktop' => 'settings/banners/only-desktop.gif',
                        'top_banner_link' => 'https://example.com/',
                    ],
                ];
            }
        };

        $banner = $service->siteTopBanner();

        $this->assertNotNull($banner['mobile']['image_url']);
        $this->assertSame($banner['desktop']['image_url'], $banner['mobile']['image_url']);
        $this->assertSame('https://example.com/', $banner['mobile']['link']);
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
                        'top_banner_image_desktop' => 'settings/banners/promo.gif',
                    ],
                ];
            }
        };

        $banner = $service->siteTopBanner();

        $this->assertFalse($banner['enabled']);
    }
}