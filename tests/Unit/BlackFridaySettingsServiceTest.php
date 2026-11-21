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

        $service->saveFromAdminForm([
            'enabled' => true,
            'nav_links' => [
                ['label' => 'محصولات', 'url' => '#products'],
            ],
            'promo_slot_1_label' => 'بنر اول',
            'promo_slot_1_image' => 'campaigns/blackfriday/promo-bolor.png',
            'promo_slot_1_url' => 'https://example.test/1',
            'promo_slot_2_label' => 'بنر دوم',
            'promo_slot_2_image' => 'campaigns/blackfriday/promo-falken.png',
            'promo_slot_2_url' => 'https://example.test/2',
            'marquee_items' => [
                ['text' => 'BLACK FRIDAY'],
            ],
        ]);

        $all = $service->all();

        $this->assertSame('#products', $all['nav_links'][0]['url']);
        $this->assertCount(2, $all['promos']);
        $this->assertSame('campaigns/blackfriday/promo-bolor.png', $all['promos'][0]['image']);
        $this->assertSame('campaigns/blackfriday/promo-falken.png', $all['promos'][1]['image']);
        $this->assertSame('BLACK FRIDAY', $all['marquee_items'][0]);
    }

    public function test_admin_form_expands_each_promo_into_its_own_slot_field(): void
    {
        $service = app(BlackFridaySettingsService::class);

        $service->saveFromAdminForm([
            'enabled' => true,
            'promo_slot_3_label' => 'وسط',
            'promo_slot_3_image' => 'campaigns/blackfriday/promo-lorian-diplomat.png',
            'promo_slot_3_url' => 'https://example.test/3',
        ]);

        $form = $service->forAdminForm();

        $this->assertSame('وسط', $form['promo_slot_3_label']);
        $this->assertSame('campaigns/blackfriday/promo-lorian-diplomat.png', $form['promo_slot_3_image']);
        $this->assertSame('', $form['promo_slot_1_image']);
    }
}