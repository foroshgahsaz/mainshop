<?php

namespace Tests\Feature;

use App\Models\CreditPlan;
use App\Models\Partner;
use App\Services\Cache\ShopCacheService;
use App\Services\Shop\EnsureDarikCreditPlan;
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

    public function test_homepage_shows_all_active_plans_without_continue_card(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            CreditPlan::query()->create([
                'name' => "طرح {$i}",
                'short_description' => "توضیح {$i}",
                'plan_url' => "/plans/plan-{$i}/",
                'position' => $i,
                'show_on_homepage_desktop' => false,
                'is_active' => true,
            ]);
        }

        CreditPlan::query()->create([
            'name' => 'طرح غیرفعال',
            'short_description' => 'مخفی',
            'plan_url' => '/plans/hidden/',
            'position' => 99,
            'show_on_homepage_desktop' => true,
            'is_active' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('طرح 1', false);
        $response->assertSee('طرح 5', false);
        $response->assertDontSee('طرح غیرفعال', false);
        $response->assertDontSee('مشاهده همه', false);
        $response->assertSee('homePlansSwiper', false);
    }

    public function test_homepage_and_plans_include_darik_after_ensure(): void
    {
        app(EnsureDarikCreditPlan::class)->ensure();
        app(ShopCacheService::class)->forgetHome();

        $this->get('/')
            ->assertOk()
            ->assertSee('داریک', false)
            ->assertSee('کیف پول اعتباری', false);

        $this->get('/plans')
            ->assertOk()
            ->assertSee('داریک', false)
            ->assertSee('/plans/darik/', false);
    }

    public function test_visiting_plans_auto_creates_missing_darik_card(): void
    {
        CreditPlan::query()
            ->where('name', EnsureDarikCreditPlan::PLAN_NAME)
            ->orWhere('plan_url', EnsureDarikCreditPlan::PLAN_URL)
            ->delete();

        app(ShopCacheService::class)->forgetHome();

        $this->assertDatabaseMissing('credit_plans', [
            'plan_url' => EnsureDarikCreditPlan::PLAN_URL,
        ]);

        $this->get('/plans')
            ->assertOk()
            ->assertSee('داریک', false);

        $this->assertDatabaseHas('credit_plans', [
            'name' => EnsureDarikCreditPlan::PLAN_NAME,
            'plan_url' => EnsureDarikCreditPlan::PLAN_URL,
            'is_active' => true,
        ]);
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
            ->assertSee('خرید اقساطی', false)
            ->assertSee('plans-index__hero', false)
            ->assertSee('plans-index__grid', false);
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
            ->assertSee('همکار تست', false)
            ->assertSee('homePartnersSwiper', false);
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
