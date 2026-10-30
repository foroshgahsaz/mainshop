<?php

namespace Tests\Feature;

use App\Models\CreditPlan;
use App\Models\Partner;
use App\Services\Cache\ShopCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditPlanAndPartnerHomepageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ShopCacheService::class)->forgetHome();
    }

    public function test_homepage_shows_four_plans_and_continue_link(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            CreditPlan::query()->create([
                'name' => "طرح {$i}",
                'short_description' => "توضیح {$i}",
                'plan_url' => "/plans/plan-{$i}/",
                'position' => $i,
                'show_on_homepage_desktop' => true,
                'is_active' => true,
            ]);
        }

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('طرح 1', false);
        $response->assertSee('طرح 4', false);
        $response->assertDontSee('طرح 5', false);
        $response->assertSee('ادامه طرح‌ها', false);
    }

    public function test_plans_index_lists_all_active_plans(): void
    {
        CreditPlan::query()->create([
            'name' => 'دیجی‌پی',
            'short_description' => 'خرید اقساطی',
            'plan_url' => '/plans/digipay-leasing/',
            'position' => 1,
            'show_on_homepage_desktop' => true,
            'is_active' => true,
        ]);

        $this->get('/plans')
            ->assertOk()
            ->assertSee('دیجی‌پی', false)
            ->assertSee('خرید اقساطی', false);
    }

    public function test_homepage_shows_partners_section(): void
    {
        Partner::query()->create([
            'name' => 'همکار تست',
            'position' => 1,
            'show_on_homepage' => true,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('همکاران ما', false)
            ->assertSee('همکار تست', false);
    }

    public function test_db_plans_replace_gateway_cards_on_homepage(): void
    {
        CreditPlan::query()->create([
            'name' => 'طرح اختصاصی',
            'short_description' => 'فقط از CRUD',
            'plan_url' => '/pages/test/',
            'position' => 0,
            'show_on_homepage_desktop' => true,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('طرح اختصاصی', false)
            ->assertSee('فقط از CRUD', false);
    }
}
