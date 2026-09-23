<?php

namespace Tests\Feature;

use App\Services\Settings\TrustBadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_trust_badges_can_be_saved_and_rendered_in_footer(): void
    {
        $service = app(TrustBadgeService::class);

        $service->saveFromAdminForm([
            [
                'title' => 'ای‌نماد',
                'type' => 'code',
                'code' => '<img src="https://example.com/enamad.png" alt="enamad" width="80" height="80">',
                'enabled' => true,
            ],
            [
                'title' => 'ترب',
                'type' => 'image',
                'image' => ['settings/trust-badges/torob.png'],
                'link' => 'https://torob.com/shop/example',
                'enabled' => true,
            ],
            [
                'title' => 'غیرفعال',
                'type' => 'code',
                'code' => '<span>hidden</span>',
                'enabled' => false,
            ],
        ]);

        $active = $service->active();

        $this->assertCount(2, $active);
        $this->assertSame('ای‌نماد', $active[0]['title']);
        $this->assertSame('ترب', $active[1]['title']);
        $this->assertSame('https://torob.com/shop/example', $active[1]['link']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('نمادهای اعتماد', false);
        $response->assertSee('enamad.png', false);
        $response->assertSee('torob.png', false);
        $response->assertDontSee('hidden', false);
    }

    public function test_footer_hides_trust_section_when_no_badges_configured(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('نمادهای اعتماد', false);
    }
}
