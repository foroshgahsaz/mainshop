<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\LoginModal;
use App\Livewire\Cart\CartPage;
use App\Livewire\Product\AddToCart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ShopLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_cart_page(): void
    {
        Livewire::test(CartPage::class)
            ->assertOk()
            ->assertSee('سبد خرید شما خالی است');
    }

    public function test_merge_guest_cart_uses_session_id_before_login(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test-merge-cart',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Merge Product',
            'slug' => 'merge-product',
            'price' => 100000,
            'stock' => 5,
            'is_active' => true,
        ]);

        Cache::put('cart:guest:pre-login-session', [
            $product->id.':0' => [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 2,
                'price' => 100000,
                'product_name' => 'Merge Product',
                'sku' => null,
            ],
        ], now()->addHour());

        $user = User::factory()->create(['phone' => '09125556677']);
        $this->actingAs($user);

        app(CartService::class)->mergeGuestCartIntoUser($user, 'pre-login-session');

        $this->assertSame(2, app(CartService::class)->count());
        $this->assertFalse(Cache::has('cart:guest:pre-login-session'));
    }

    public function test_logged_in_placeholder_user_opens_profile_step_in_modal(): void
    {
        $user = User::factory()->create([
            'phone' => '09907891090',
            'name' => 'کاربر 1090',
        ]);

        Livewire::actingAs($user)
            ->test(LoginModal::class)
            ->dispatch('open-login-modal', redirect: route('checkout'))
            ->assertSet('step', 'profile')
            ->assertSet('phone', '09907891090');
    }

    public function test_placeholder_user_gets_mandatory_profile_modal_on_page_load(): void
    {
        $user = User::factory()->create([
            'phone' => '09907891090',
            'name' => 'کاربر 1090',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-profile-required', false)
            ->assertSee('تکمیل پروفایل');

        Livewire::actingAs($user)
            ->test(LoginModal::class)
            ->assertSet('step', 'profile')
            ->assertDontSeeHtml('aria-label="بستن"')
            ->call('closeModal')
            ->assertHasErrors(['profileName'])
            ->set('profileName', 'مریم کریمی')
            ->set('nationalCode', '0013542419')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame('مریم کریمی', $user->fresh()->name);
        $this->assertSame('0013542419', $user->fresh()->national_code);
        $this->assertSame('0013542419@chinibazar.ir', $user->fresh()->email);

        $this->actingAs($user->fresh())
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-profile-required', false)
            ->assertSee('مریم کریمی');
    }

    public function test_new_otp_user_is_prompted_for_name_and_keeps_guest_cart(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test-otp-profile',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cart Keep Product',
            'slug' => 'cart-keep-product',
            'price' => 50000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->get(route('home'))->assertOk();
        $guestSessionId = session()->getId();
        app(CartService::class)->add($product->id, 1);

        $phone = '09128887766';

        Livewire::test(LoginModal::class)
            ->dispatch('open-login-modal', redirect: route('cart'))
            ->set('phone', $phone)
            ->call('sendOtp')
            ->set('otp', Cache::get('otp:phone:'.$phone))
            ->call('verifyOtp')
            ->assertSet('step', 'profile')
            ->set('profileName', 'علی رضایی')
            ->set('nationalCode', '0067749828')
            ->call('saveProfile')
            ->assertRedirect(route('cart'));

        $this->assertAuthenticated();

        $user = User::where('phone', $phone)->firstOrFail();
        $this->assertSame('علی رضایی', $user->fresh()->name);
        $this->assertSame('0067749828', $user->fresh()->national_code);
        $this->assertSame(1, app(CartService::class)->count());
        $this->assertFalse(Cache::has('cart:guest:'.$guestSessionId));
    }

    public function test_guest_cart_page_renders_invoice_for_string_item_keys(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test-guest-cart',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Guest Cart Product',
            'slug' => 'guest-cart-product',
            'price' => 150000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->get(route('home'))->assertOk();
        app(CartService::class)->add($product->id, 2);

        Livewire::test(CartPage::class)
            ->assertOk()
            ->assertSee('فاکتور سبد خرید')
            ->assertSee('Guest Cart Product')
            ->assertSee('300,000');
    }

    public function test_home_mobile_header_keeps_phone_and_opens_cart_sidebar(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('header-phone-btn', false)
            ->assertSee('header-phone-btn__waves', false)
            ->assertSee('data-open-cart', false);
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
            ->assertSet('step', 'profile')
            ->set('profileName', 'رضا محمدی')
            ->set('nationalCode', '0013542419')
            ->call('saveProfile')
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
            ->assertSee('Test Product')
            ->assertSee('فاکتور سبد خرید')
            ->assertSee('شرح کالا')
            ->assertSee('مبلغ قابل پرداخت');
    }

    public function test_returning_user_with_complete_profile_skips_profile_step(): void
    {
        $phone = '09124445566';

        User::factory()->create([
            'phone' => $phone,
            'name' => 'سارا احمدی',
            'national_code' => '0013542419',
        ]);

        Livewire::test(LoginModal::class)
            ->dispatch('open-login-modal', redirect: route('home'))
            ->set('phone', $phone)
            ->call('sendOtp')
            ->set('otp', Cache::get('otp:phone:'.$phone))
            ->call('verifyOtp')
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_find_by_login_phone_matches_legacy_international_format(): void
    {
        $user = User::factory()->create([
            'phone' => '+989124445566',
            'name' => 'رضا محمدی',
            'national_code' => '0067749828',
        ]);

        $found = User::findByLoginPhone('09124445566');

        $this->assertNotNull($found);
        $this->assertSame($user->getKey(), $found->getKey());
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
