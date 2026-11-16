<?php

namespace Tests\Feature;

use App\Services\Cache\ShopCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlansDarikPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_darik_plan_page_is_available(): void
    {
        $this->get('/plans/darik/')
            ->assertOk()
            ->assertSee('کیف پول داریک', false)
            ->assertSee('5892', false);
    }

    public function test_darik_plan_redirects_without_trailing_slash(): void
    {
        $this->get('/plans/darik')
            ->assertRedirect('/plans/darik/');
    }

    public function test_plans_index_includes_darik_credit_plan_from_migration(): void
    {
        app(ShopCacheService::class)->forgetHome();

        $this->assertDatabaseHas('credit_plans', [
            'name' => 'داریک',
            'plan_url' => '/plans/darik/',
        ]);

        $this->get('/plans')
            ->assertOk()
            ->assertSee('داریک', false)
            ->assertSee('کیف پول اعتباری', false)
            ->assertSee('/plans/darik/', false);
    }
}
