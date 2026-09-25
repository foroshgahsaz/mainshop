<?php

namespace Tests\Feature;

use App\Services\Settings\FooterSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_settings_can_be_saved_and_rendered(): void
    {
        $service = app(FooterSettingsService::class);

        $service->saveFromAdminForm([
            'features' => [
                [
                    'title' => 'ارسال فوری',
                    'subtitle' => 'همان روز در تهران',
                    'enabled' => true,
                ],
            ],
            'brand_description' => 'توضیح تستی فوتر',
            'phone' => '021-1111-2222',
            'mobile' => '0912-333-4444',
            'address' => 'آدرس تستی فوتر',
            'instagram' => 'https://instagram.com/chinibazar',
            'telegram' => '',
            'whatsapp' => '',
            'linkedin' => '',
            'quick_links_title' => 'لینک‌های مهم',
            'quick_links' => [
                [
                    'label' => 'صفحه تست',
                    'url' => '/test-footer-link',
                    'enabled' => true,
                ],
            ],
            'about_paragraphs' => [
                ['text' => 'پاراگراف اول تست'],
                ['text' => 'پاراگراف دوم تست'],
            ],
            'copyright' => '© تست فوتر',
            'bottom_links' => [
                [
                    'label' => 'قوانین تست',
                    'url' => '/terms-test',
                    'enabled' => true,
                ],
            ],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('توضیح تستی فوتر', false);
        $response->assertSee('021-1111-2222', false);
        $response->assertSee('0912-333-4444', false);
        $response->assertSee('آدرس تستی فوتر', false);
        $response->assertSee('لینک‌های مهم', false);
        $response->assertSee('صفحه تست', false);
        $response->assertSee('/test-footer-link', false);
        $response->assertSee('پاراگراف اول تست', false);
        $response->assertSee('© تست فوتر', false);
        $response->assertSee('قوانین تست', false);
        $response->assertSee('ارسال فوری', false);
        $response->assertSee('https://instagram.com/chinibazar', false);
    }

    public function test_footer_uses_defaults_when_not_configured(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('دسترسی سریع', false);
        $response->assertSee('021-9100-1234', false);
        $response->assertSee('ارسال سریع', false);
    }
}
