<?php

namespace Tests\Feature;

use App\Models\CreditPlan;
use App\Services\Shop\CreditPlanGatewaySchemaRepair;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreditPlanGatewaySchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_is_idempotent_when_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('credit_plans', 'credit_gateway'));
        $this->assertTrue(Schema::hasColumn('credit_plans', 'cash_gateway'));

        CreditPlanGatewaySchemaRepair::forgetCache();
        $actions = app(CreditPlanGatewaySchemaRepair::class)->ensure();

        $this->assertSame([], $actions);
        $this->artisan('shop:repair-credit-plan-gateways')->assertSuccessful();
    }

    public function test_credit_plan_can_persist_gateways_after_repair(): void
    {
        CreditPlanGatewaySchemaRepair::forgetCache();
        app(CreditPlanGatewaySchemaRepair::class)->ensureOnce();

        $plan = CreditPlan::query()->create([
            'name' => 'تست درگاه',
            'plan_url' => '/plans/test/',
            'credit_gateway' => 'digipay',
            'cash_gateway' => 'parsian',
            'is_active' => true,
        ]);

        $this->assertSame('digipay', $plan->fresh()->credit_gateway);
        $this->assertSame('parsian', $plan->fresh()->cash_gateway);
    }
}
