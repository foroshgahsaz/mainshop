<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\ProductFamily;
use App\Models\ProductTemplate;
use App\Services\Shop\ChineseServiceBrandPopupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChineseServiceBrandPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_lists_configured_brands_for_chinese_service_family(): void
    {
        $family = ProductFamily::query()->create([
            'name' => 'سرویس چینی',
            'slug' => 'chinese-service',
            'is_active' => true,
            'position' => 0,
        ]);

        ProductTemplate::query()->create([
            'product_family_id' => $family->id,
            'name' => 'مارشال',
            'slug' => 'marshal',
            'is_active' => true,
            'position' => 0,
        ]);

        $payload = app(ChineseServiceBrandPopupService::class)->payload();

        $this->assertNotNull($payload);
        $this->assertSame($family->id, $payload['family_id']);
        $this->assertCount(6, $payload['brands']);
        $this->assertSame('مارشال', $payload['brands'][1]['name']);
        $this->assertStringContainsString('product_template_id=', $payload['brands'][1]['url']);
    }
}
