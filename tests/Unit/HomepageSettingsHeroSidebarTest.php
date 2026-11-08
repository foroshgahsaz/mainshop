<?php

namespace Tests\Unit;

use App\Services\Settings\HomepageSettingsService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSettingsHeroSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_save_preserves_legacy_hero_json_in_settings(): void
    {
        app(SettingsService::class)->set('homepage', 'data', json_encode([
            'taxonomy_mode' => 'categories',
            'hero_sidebar_title' => 'سرویس چینی',
            'hero_sidebar_links' => [
                ['label' => 'والاس', 'url' => 'https://www.chinibazar.ir/brands/valas', 'is_active' => true],
            ],
            'hero_quick_category_ids' => [],
            'new_products_enabled' => true,
            'new_product_ids' => [],
        ], JSON_UNESCAPED_UNICODE));

        app(HomepageSettingsService::class)->saveFromAdminForm([
            'taxonomy_mode' => 'categories',
            'new_products_enabled' => true,
            'new_product_ids' => '',
        ]);

        $links = app(HomepageSettingsService::class)->heroSidebarLinks();

        $this->assertSame('والاس', $links[0]['label'] ?? null);
    }
}
