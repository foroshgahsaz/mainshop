<?php

namespace Tests\Feature;

use App\Models\ProductFamily;
use App\Models\ProductFamilyHomepagePopupItem;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Settings\SettingsService;
use App\Services\Shop\ProductFamilyHomepagePopupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFamilyHomepagePopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_payload_uses_admin_links(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-service',
            'is_active' => true,
            'homepage_popup_enabled' => true,
            'position' => 0,
        ]);

        ProductFamilyHomepagePopupItem::query()->create([
            'product_family_id' => $family->id,
            'label' => 'والاس',
            'url' => 'https://example.test/wallace',
            'position' => 0,
            'is_active' => true,
        ]);

        $payload = app(ProductFamilyHomepagePopupService::class)->payloadForFamily($family);

        $this->assertNotNull($payload);
        $this->assertSame('والاس', $payload['items'][0]['label']);
        $this->assertSame('https://example.test/wallace', $payload['items'][0]['url']);
    }

    public function test_home_payload_includes_family_popups_map(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'تست',
            'slug' => 'test-family',
            'is_active' => true,
            'homepage_popup_enabled' => true,
            'position' => 0,
        ]);

        ProductFamilyHomepagePopupItem::query()->create([
            'product_family_id' => $family->id,
            'label' => 'لینک',
            'url' => 'https://example.test/x',
            'position' => 0,
            'is_active' => true,
        ]);

        app(SettingsService::class)->set('homepage', 'data', json_encode([
            'taxonomy_mode' => HomepageSettingsService::TAXONOMY_PRODUCT_FAMILIES,
            'new_products_enabled' => true,
            'new_product_ids' => [],
        ], JSON_UNESCAPED_UNICODE));
        app(ShopCacheService::class)->forgetHome();

        $map = app(ShopCacheService::class)->homePayload()['family_homepage_popups'] ?? [];

        $this->assertArrayHasKey($family->id, $map);
    }
}
