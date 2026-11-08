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

    public function test_hero_sidebar_links_work_without_popup_toggle(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'تست',
            'slug' => 'test-family',
            'is_active' => true,
            'homepage_popup_enabled' => false,
            'position' => 0,
        ]);

        ProductFamilyHomepagePopupItem::query()->create([
            'product_family_id' => $family->id,
            'label' => 'برند',
            'url' => 'https://example.test/brand',
            'position' => 0,
            'is_active' => true,
        ]);

        $items = app(ProductFamilyHomepagePopupService::class)->activeLinkItemsForFamily($family);

        $this->assertCount(1, $items);
        $this->assertSame('برند', $items[0]['label']);
    }

    public function test_home_payload_omits_legacy_hero_hub(): void
    {
        app(ShopCacheService::class)->forgetHome();

        $payload = app(ShopCacheService::class)->homePayload();

        $this->assertArrayNotHasKey('hero_hub', $payload);
    }
}
