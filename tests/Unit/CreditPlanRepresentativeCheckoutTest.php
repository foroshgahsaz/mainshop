<?php

namespace Tests\Unit;

use App\Models\CreditPlan;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditPlanRepresentativeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_includes_cash_only_credit_only_and_split_plans(): void
    {
        app(SettingsService::class)->setMany('tara', ['enabled' => true]);
        app(SettingsService::class)->setMany('parsian', ['enabled' => true]);
        app(SettingsService::class)->setMany('zarinpal', ['enabled' => false]);
        $this->app->forgetInstance(PaymentGatewayCatalog::class);

        $split = CreditPlan::query()->create([
            'name' => 'طرح ترکیبی',
            'plan_url' => '/plans/combo/',
            'credit_gateway' => 'tara',
            'cash_gateway' => 'parsian',
            'position' => 1,
            'is_active' => true,
        ]);

        $cashOnly = CreditPlan::query()->create([
            'name' => 'دیبا فقط نقدی',
            'plan_url' => '/plans/diba/',
            'credit_gateway' => null,
            'cash_gateway' => 'parsian',
            'position' => 2,
            'is_active' => true,
        ]);

        $creditOnly = CreditPlan::query()->create([
            'name' => 'فقط اعتباری',
            'plan_url' => '/plans/credit-only/',
            'credit_gateway' => 'tara',
            'cash_gateway' => null,
            'position' => 3,
            'is_active' => true,
        ]);

        CreditPlan::query()->create([
            'name' => 'درگاه نقدی خاموش',
            'plan_url' => '/plans/off-cash/',
            'credit_gateway' => null,
            'cash_gateway' => 'zarinpal',
            'position' => 4,
            'is_active' => true,
        ]);

        CreditPlan::query()->create([
            'name' => 'بدون درگاه',
            'plan_url' => '/plans/none/',
            'credit_gateway' => null,
            'cash_gateway' => null,
            'position' => 5,
            'is_active' => true,
        ]);

        CreditPlan::query()->create([
            'name' => 'غیرفعال',
            'plan_url' => '/plans/inactive/',
            'credit_gateway' => 'tara',
            'cash_gateway' => 'parsian',
            'position' => 6,
            'is_active' => false,
        ]);

        $ids = CreditPlan::query()->forRepresentativeCheckout()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame(
            [(int) $split->id, (int) $cashOnly->id, (int) $creditOnly->id],
            $ids,
        );
        $this->assertTrue($cashOnly->fresh()->isCashOnlyCheckout());
        $this->assertTrue($creditOnly->fresh()->isCreditOnlyCheckout());
        $this->assertTrue($split->fresh()->supportsSplitCheckout());
    }
}
