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

    public function test_scope_for_representative_checkout_requires_both_enabled_gateways(): void
    {
        app(SettingsService::class)->setMany('tara', ['enabled' => true]);
        app(SettingsService::class)->setMany('parsian', ['enabled' => true]);
        app(SettingsService::class)->setMany('zarinpal', ['enabled' => false]);
        $this->app->forgetInstance(PaymentGatewayCatalog::class);

        $eligible = CreditPlan::query()->create([
            'name' => 'طرح ترکیبی',
            'plan_url' => '/plans/combo/',
            'credit_gateway' => 'tara',
            'cash_gateway' => 'parsian',
            'position' => 1,
            'is_active' => true,
        ]);

        CreditPlan::query()->create([
            'name' => 'بدون درگاه نقدی',
            'plan_url' => '/plans/no-cash/',
            'credit_gateway' => 'tara',
            'cash_gateway' => null,
            'position' => 2,
            'is_active' => true,
        ]);

        CreditPlan::query()->create([
            'name' => 'درگاه نقدی خاموش',
            'plan_url' => '/plans/off-cash/',
            'credit_gateway' => 'tara',
            'cash_gateway' => 'zarinpal',
            'position' => 3,
            'is_active' => true,
        ]);

        CreditPlan::query()->create([
            'name' => 'غیرفعال',
            'plan_url' => '/plans/inactive/',
            'credit_gateway' => 'tara',
            'cash_gateway' => 'parsian',
            'position' => 4,
            'is_active' => false,
        ]);

        $ids = CreditPlan::query()->forRepresentativeCheckout()->pluck('id')->all();

        $this->assertSame([(int) $eligible->id], array_map('intval', $ids));
        $this->assertTrue($eligible->fresh()->hasCheckoutGateways());
        $this->assertNotEmpty(app(PaymentGatewayCatalog::class)->credit());
        $this->assertNotEmpty(app(PaymentGatewayCatalog::class)->cash());
    }
}
