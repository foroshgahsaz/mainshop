<?php

namespace Tests\Unit;

use App\Models\ProductFamily;
use App\Models\ProductFamilyHomepagePopupItem;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Settings\SettingsService;
use App\Services\Shop\HomepageHeroHubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageHeroHubServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_uses_family_with_links_when_configured_family_is_empty(): void
    {
        $empty = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-service-empty',
            'is_active' => true,
            'position' => 0,
        ]);

        $withLinks = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-service',
            'is_active' => true,
            'position' => 1,
        ]);

        ProductFamilyHomepagePopupItem::query()->create([
            'product_family_id' => $withLinks->id,
            'label' => 'والاس',
            'url' => 'https://example.test/wallace',
            'position' => 1,
            'is_active' => true,
        ]);

        app(SettingsService::class)->set('homepage', 'data', json_encode([
            'taxonomy_mode' => 'categories',
            'hero_sidebar_family_id' => $empty->id,
            'hero_quick_family_ids' => [],
            'new_products_enabled' => true,
            'new_product_ids' => [],
        ], JSON_UNESCAPED_UNICODE));

        $payload = app(HomepageHeroHubService::class)->payload();

        $this->assertSame('والاس', $payload['sidebar_links'][0]['label'] ?? null);
    }
}
