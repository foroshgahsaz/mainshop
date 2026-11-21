<?php

namespace Tests\Unit;

use App\Services\Settings\BlackFridaySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlackFridaySettingsServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_default_hero_intro_does_not_contain_neon_phrase(): void
    {
        $intro = app(BlackFridaySettingsService::class)->defaults()['hero_intro'];

        $this->assertStringNotContainsString('نئون', $intro);
        $this->assertStringContainsString('توی تاریکی غرق شو', $intro);
    }

    public function test_normalize_keeps_nav_and_promo_items(): void
    {
        $service = app(BlackFridaySettingsService::class);

        $saved = [
            'enabled' => true,
            'nav_links' => [
                ['label' => 'محصولات', 'url' => '#products'],
            ],
            'promos' => [
                [
                    'label' => 'نمونه',
                    'image' => 'campaigns/blackfriday/promo-bolor.png',
                    'url' => 'https://example.test',
                ],
            ],
            'marquee_items' => [
                ['text' => 'BLACK FRIDAY'],
            ],
        ];

        $service->saveFromAdminForm($saved);

        $all = $service->all();

        $this->assertSame('#products', $all['nav_links'][0]['url']);
        $this->assertSame('campaigns/blackfriday/promo-bolor.png', $all['promos'][0]['image']);
        $this->assertSame('BLACK FRIDAY', $all['marquee_items'][0]);
    }
}