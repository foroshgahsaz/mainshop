<?php

namespace Tests\Feature;

use App\Services\Settings\SiteHeaderSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteHeaderSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_header_snippet_renders_in_head_when_enabled(): void
    {
        $service = app(SiteHeaderSettingsService::class);

        $service->saveFromAdminForm([
            'snippets' => [
                [
                    'label' => 'تست آنالیتیکس',
                    'enabled' => true,
                    'defer' => false,
                    'code' => '<meta name="x-site-header-test" content="header-snippet-ok">',
                ],
            ],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('name="x-site-header-test" content="header-snippet-ok"', false);
    }

    public function test_disabled_site_header_snippet_is_not_rendered(): void
    {
        $service = app(SiteHeaderSettingsService::class);

        $service->saveFromAdminForm([
            'snippets' => [
                [
                    'label' => 'غیرفعال',
                    'enabled' => false,
                    'defer' => false,
                    'code' => '<meta name="x-site-header-hidden" content="should-not-appear">',
                ],
            ],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('x-site-header-hidden', false);
    }

    public function test_deferred_site_header_snippet_includes_loader(): void
    {
        $service = app(SiteHeaderSettingsService::class);

        $service->saveFromAdminForm([
            'snippets' => [
                [
                    'label' => 'تأخیری',
                    'enabled' => true,
                    'defer' => true,
                    'code' => '<script>window.__deferredHeaderTest = 1;</script>',
                ],
            ],
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('injectSiteHeaderSnippet', false);
        $response->assertSee('__deferredHeaderTest', false);
    }
}
