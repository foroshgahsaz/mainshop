<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CouponService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CouponValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimum_order_amount_returns_detailed_message(): void
    {
        $user = User::factory()->create();
        Coupon::query()->create([
            'code' => 'BIG10M',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 10,
            'minimum_order_amount' => 10_000_000,
            'is_active' => true,
        ]);

        $result = app(CouponService::class)->validate('BIG10M', $user, 500_000);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('حداقل مبلغ سبد', $result['message']);
        $this->assertStringContainsString('10', $result['message']);
    }

    public function test_gateway_mismatch_returns_allowed_gateways_message(): void
    {
        app(SettingsService::class)->set('zarinpal', 'enabled', true);
        app(SettingsService::class)->set('tara', 'enabled', true);

        $user = User::factory()->create();
        Coupon::query()->create([
            'code' => 'TARAONLY',
            'type' => Coupon::TYPE_FIXED,
            'value' => 50_000,
            'is_active' => true,
            'allowed_payment_gateways' => ['tara'],
        ]);

        $service = app(CouponService::class);

        $invalid = $service->validate('TARAONLY', $user, 1_000_000, 'zarinpal');
        $this->assertFalse($invalid['valid']);
        $this->assertStringContainsString('درگاه', $invalid['message']);

        $valid = $service->validate('TARAONLY', $user, 1_000_000, 'tara');
        $this->assertTrue($valid['valid']);
    }

    public function test_checkout_keeps_form_when_coupon_invalid(): void
    {
        [$user] = $this->prepareCart();

        Coupon::query()->create([
            'code' => 'MIN5M',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 5,
            'minimum_order_amount' => 5_000_000,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('couponCode', 'MIN5M')
            ->assertSee('آدرس تحویل')
            ->assertSee('حداقل مبلغ سبد')
            ->assertDontSee('سبد خرید خالی است');
    }

    /**
     * @return array{0: User}
     */
    protected function prepareCart(): array
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Product',
            'slug' => 'product-'.uniqid(),
            'price' => 100_000,
            'stock' => 5,
            'is_active' => true,
        ]);
        ShippingMethod::query()->create([
            'name' => 'پست',
            'price' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        app(CartService::class)->add($product->id, 1);

        return [$user];
    }
}
