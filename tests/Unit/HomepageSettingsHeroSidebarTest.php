<?php

namespace Tests\Unit;

use App\Models\ProductFamily;
use App\Models\ProductFamilyHomepagePopupItem;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Settings\SettingsService;
use App\Services\Shop\HomepageHeroHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSettingsHeroSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_json_sidebar_links_render_on_site_payload(): void
    {
        app(SettingsService::class)->set('homepage', 'data', json_encode([
            'taxonomy_mode' => 'categories',
            'hero_sidebar_family_id' => null,
            'hero_sidebar_title' => 'سرویس چینی',
            'hero_sidebar_links' => [
                ['label' => 'والاس', 'url' => 'https://www.chinibazar.ir/brands/valas'],
            ],
            'hero_quick_family_ids' => [],
            'new_products_enabled' => true,
            'new_product_ids' => [],
        ], JSON_UNESCAPED_UNICODE));

        app(ShopCacheService::class)->forgetHome();

        $hero = app(HomepageHeroHubService::class)->payload();

        $this->assertSame('والاس', $hero['sidebar_links'][0]['label'] ?? null);
    }

    public function test_save_homepage_settings_imports_links_from_family_when_repeater_empty(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-service',
            'is_active' => true,
            'position' => 0,
        ]);

        ProductFamilyHomepagePopupItem::query()->create([
            'product_family_id' => $family->id,
            'label' => 'مارشال',
            'url' => 'https://example.test/marshal',
            'position' => 1,
            'is_active' => true,
        ]);

        app(HomepageSettingsService::class)->saveFromAdminForm([
            'taxonomy_mode' => 'categories',
            'hero_sidebar_family_id' => $family->id,
            'hero_sidebar_title' => 'سرویس چینی',
            'hero_sidebar_links' => [],
            'hero_quick_family_ids' => [],
            'new_products_enabled' => true,
            'new_product_ids' => '',
        ]);

        $links = app(HomepageSettingsService::class)->heroSidebarLinks();

        $this->assertSame('مارشال', $links[0]['label'] ?? null);
    }
}
