<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Cart\CartPage;
use App\Livewire\Product\AddToCart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ShopLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_cart_page(): void
    {
        Livewire::test(CartPage::class)->assertOk();
    }

    public function test_user_can_login_with_otp_and_add_to_cart(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'slug' => 'test-product',
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $phone = '09121111111';

        Livewire::test(Login::class)
            ->set('phone', $phone)
            ->call('sendOtp')
            ->assertSet('step', 'otp');

        $code = Cache::get('otp:phone:'.$phone);

        Livewire::test(Login::class)
            ->set('phone', $phone)
            ->set('step', 'otp')
            ->set('otp', $code)
            ->call('verifyOtp')
            ->assertRedirect(route('account.dashboard'));

        $user = User::where('phone', $phone)->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->phone_verified_at);

        Livewire::actingAs($user)
            ->test(AddToCart::class, ['product' => $product])
            ->set('quantity', 2)
            ->call('add');

        Livewire::actingAs($user)
            ->test(CartPage::class)
            ->assertSee('Test Product');
    }

    public function test_otp_service_sends_via_log_driver(): void
    {
        $otp = app(OtpService::class);
        $otp->send('09123334444');

        $this->assertTrue(Cache::has('otp:phone:09123334444'));
    }

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->canAccessPanel(filament()->getCurrentPanel() ?? filament()->getDefaultPanel()));
    }
}
