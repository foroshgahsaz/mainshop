<?php

namespace Tests\Feature;

use App\Services\Cache\ShopCacheService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageHeroHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_hero_hub_and_gateway_card(): void
    {
        app(SettingsService::class)->setMany('digipay', [
            'enabled' => '1',
            'show_on_homepage' => '1',
            'homepage_plan_url' => '/plans/digipay-leasing/',
            'homepage_intro' => 'خرید اعتباری تست',
        ]);
        app(ShopCacheService::class)->forgetHome();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('home-hero-hub', false);
        $response->assertSee('home-gateway-plan-card', false);
        $response->assertSee('خرید اعتباری تست', false);
        $response->assertDontSee('دسته‌بندی محصولات', false);
    }
}
