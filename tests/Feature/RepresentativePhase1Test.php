<?php

namespace Tests\Feature;

use App\Models\Province;
use App\Models\User;
use App\Services\Auth\LoginRedirectService;
use App\Services\Representative\RepresentativeCustomerService;
use Database\Seeders\IranLocationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RepresentativePhase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IranLocationsSeeder::class);
    }

    public function test_representative_shop_login_redirects_to_representative_panel(): void
    {
        $rep = User::factory()->create([
            'is_representative' => true,
            'is_admin' => false,
            'status' => true,
        ]);

        $url = app(LoginRedirectService::class)->shopDefaultUrl($rep);

        $this->assertStringContainsString('/representative', $url);
    }

    public function test_representative_can_create_customer_with_unique_phone(): void
    {
        $rep = User::factory()->create([
            'is_representative' => true,
            'status' => true,
        ]);

        $province = Province::query()->firstOrFail();
        $city = $province->cities()->firstOrFail();

        $customer = app(RepresentativeCustomerService::class)->create($rep, [
            'name' => 'مشتری تست',
            'phone' => '09121234567',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'خیابان تست',
        ]);

        $this->assertSame($rep->id, $customer->created_by_representative_id);
        $this->assertTrue($customer->addresses()->exists());
    }

    public function test_duplicate_phone_throws_validation_error(): void
    {
        User::factory()->create(['phone' => '09121111111']);

        $rep = User::factory()->create(['is_representative' => true, 'status' => true]);
        $province = Province::query()->firstOrFail();
        $city = $province->cities()->firstOrFail();

        $this->expectException(ValidationException::class);

        app(RepresentativeCustomerService::class)->create($rep, [
            'name' => 'دیگر',
            'phone' => '09121111111',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس',
        ]);
    }

    public function test_representative_can_access_representative_panel_only(): void
    {
        $rep = User::factory()->create([
            'is_representative' => true,
            'is_admin' => false,
            'status' => true,
        ]);

        $panel = \Filament\Facades\Filament::getPanel('representative');

        $this->assertTrue($rep->canAccessPanel($panel));
        $this->assertFalse($rep->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));
    }
}
