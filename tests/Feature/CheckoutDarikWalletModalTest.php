<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutDarikWalletModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_darik_opens_wallet_id_modal(): void
    {
        $this->enableDarik();
        [$user] = $this->prepareCart();

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSet('showDarikWalletModal', false)
            ->set('paymentMethod', 'darik')
            ->assertSet('showDarikWalletModal', true)
            ->assertSee('شناسه کیف پول داریک')
            ->assertSee('تأیید و ادامه خرید')
            ->assertSeeHtml('checkout-darik-modal--open');
    }

    public function test_confirm_darik_wallet_closes_modal_and_keeps_code(): void
    {
        $this->enableDarik();
        [$user] = $this->prepareCart();

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('paymentMethod', 'darik')
            ->set('darikMemberCodeDraft', '5892251560796087')
            ->call('confirmDarikWallet')
            ->assertHasNoErrors()
            ->assertSet('showDarikWalletModal', false)
            ->assertSet('paymentMethod', 'darik')
            ->assertSet('darikMemberCode', '5892251560796087')
            ->assertSee('5892251560796087');
    }

    public function test_cancel_darik_modal_without_code_reverts_payment_method(): void
    {
        $this->enableDarik();
        [$user] = $this->prepareCart();

        $component = Livewire::actingAs($user)
            ->test(CheckoutPage::class);

        $fallback = $component->get('paymentMethod');

        $component
            ->set('paymentMethod', 'darik')
            ->assertSet('showDarikWalletModal', true)
            ->call('cancelDarikWalletModal')
            ->assertSet('showDarikWalletModal', false)
            ->assertSet('paymentMethod', $fallback)
            ->assertSet('darikMemberCode', '');
    }

    protected function enableDarik(): void
    {
        app(SettingsService::class)->set('darik', 'enabled', true);
    }

    /**
     * @return array{0: User, 1: Product, 2: ShippingMethod}
     */
    protected function prepareCart(): array
    {
        $user = User::factory()->create(['darik_member_code' => null]);
        $category = Category::query()->create([
            'name' => 'Test',
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Product',
            'slug' => 'product-'.uniqid(),
            'price' => 100000,
            'stock' => 5,
            'is_active' => true,
        ]);
        $shipping = ShippingMethod::query()->create([
            'name' => 'پست پیشتاز',
            'price' => 25000,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        app(CartService::class)->add($product->id, 1);

        return [$user, $product, $shipping];
    }
}
