<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Cart\CartService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use Database\Seeders\IranLocationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_address_on_checkout_without_leaving_the_page(): void
    {
        $this->seed(IranLocationsSeeder::class);
        [$user, , $shipping] = $this->prepareCart();
        $province = \App\Models\Province::query()->where('name', 'تهران')->firstOrFail();
        $city = \App\Models\City::query()->where('province_id', $province->id)->where('name', 'تهران')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSet('showAddressForm', true)
            ->assertSee('ذخیره آدرس')
            ->assertSee('برای ادامه، ابتدا آدرس تحویل را ذخیره کنید')
            ->assertDontSee(route('account.addresses'), false)
            ->set('receiver_name', 'رضا محمدی')
            ->set('receiver_phone', '09121112233')
            ->set('province_id', $province->id)
            ->set('city_id', $city->id)
            ->set('address', 'خیابان ولیعصر، پلاک ۱۲')
            ->set('postal_code', '1234567890')
            ->call('saveAddress')
            ->assertHasNoErrors()
            ->assertSet('showAddressForm', false)
            ->assertSee('رضا محمدی')
            ->assertDontSee('برای ادامه، ابتدا آدرس تحویل را ذخیره کنید');

        $address = UserAddress::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($address);
        $this->assertSame('تهران', $address->city);
        $this->assertTrue($address->is_default);
        $this->assertSame($address->id, $component->get('addressId'));
        $this->assertSame($shipping->id, $component->get('shippingMethodId'));
    }

    public function test_checkout_can_save_display_name_from_inline_profile_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'کاربر 1090',
            'phone' => '09907891090',
        ]);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSet('needsProfileNames', true)
            ->set('profileFirstName', 'مریم')
            ->set('profileLastName', 'کریمی')
            ->call('saveCheckoutProfileNames')
            ->assertHasNoErrors()
            ->assertSet('needsProfileNames', false)
            ->assertSet('receiver_name', 'مریم کریمی');

        $this->assertSame('مریم کریمی', $user->fresh()->name);
    }

    public function test_checkout_prefills_receiver_name_from_saved_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'سارا احمدی',
            'phone' => '09123334455',
        ]);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSet('receiver_name', 'سارا احمدی');
    }

    public function test_checkout_updates_receiver_name_after_profile_saved_event(): void
    {
        $user = User::factory()->create([
            'name' => 'کاربر 3455',
            'phone' => '09123334455',
        ]);

        $component = Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSet('receiver_name', '');

        $user->forceFill(['name' => 'سارا احمدی'])->save();
        $this->actingAs($user->fresh());

        $component
            ->dispatch('shop-user-profile-updated')
            ->assertSet('receiver_name', 'سارا احمدی');
    }

    public function test_checkout_rejects_invalid_postal_code(): void
    {
        $this->seed(IranLocationsSeeder::class);
        [$user] = $this->prepareCart();
        $province = \App\Models\Province::query()->orderBy('position')->firstOrFail();
        $city = \App\Models\City::query()->where('province_id', $province->id)->orderBy('position')->firstOrFail();

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('receiver_name', 'رضا محمدی')
            ->set('receiver_phone', '09121112233')
            ->set('province_id', $province->id)
            ->set('city_id', $city->id)
            ->set('address', 'خیابان تست')
            ->set('postal_code', '123')
            ->call('saveAddress')
            ->assertHasErrors(['postal_code']);
    }

    public function test_checkout_shows_shipping_and_gateway_icons(): void
    {
        [$user, , $shipping] = $this->prepareCart();

        $shipping->update(['icon' => 'shipping-icons/post.png']);
        app(SettingsService::class)->set('zarinpal', 'icon', 'gateway-icons/zarinpal.png');

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->assertSee(ShopMedia::url('shipping-icons/post.png'), false)
            ->assertSee(ShopMedia::url('gateway-icons/zarinpal.png'), false)
            ->assertSee('آدرس تحویل')
            ->assertSee('نحوه ارسال')
            ->assertSee('کد تخفیف')
            ->assertSee('انتخاب درگاه')
            ->assertSee('خلاصه سفارش');
    }

    public function test_gateway_catalog_exposes_uploaded_icon_url(): void
    {
        app(SettingsService::class)->set('tara', 'icon', 'gateway-icons/tara.png');

        $catalog = app(PaymentGatewayCatalog::class);

        $this->assertSame(ShopMedia::url('gateway-icons/tara.png'), $catalog->definition('tara')['icon']);
        $this->assertNull($catalog->definition('zarinpal')['icon']);
    }

    /**
     * @return array{0: User, 1: Product, 2: ShippingMethod}
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
