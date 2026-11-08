<?php

namespace Tests\Unit;

use App\Services\Cache\ShopCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageHeroHubServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_payload_no_longer_includes_hero_hub_sidebar(): void
    {
        $payload = app(ShopCacheService::class)->homePayload();

        $this->assertArrayNotHasKey('hero_hub', $payload);
    }
}
