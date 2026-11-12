<?php

namespace Tests\Feature;

use App\Models\Province;
use App\Models\User;
use App\Services\Representative\RepresentativeCustomerService;
use Database\Seeders\IranLocationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerTransferOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IranLocationsSeeder::class);
    }

    public function test_lookup_reports_current_representative_for_existing_national_code(): void
    {
        $owner = User::factory()->create(['is_representative' => true, 'status' => true, 'name' => 'نماینده الف']);
        $other = User::factory()->create(['is_representative' => true, 'status' => true, 'name' => 'نماینده ب']);

        $province = Province::query()->firstOrFail();
        $city = $province->cities()->firstOrFail();

        $customer = app(RepresentativeCustomerService::class)->create($owner, [
            'name' => 'مشتری',
            'phone' => '09120001111',
            'national_code' => '0013542419',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس تست',
        ]);

        $info = app(RepresentativeCustomerService::class)->lookupByNationalCode('0013542419', $other);

        $this->assertNotNull($info);
        $this->assertSame($customer->id, $info['customer']->id);
        $this->assertFalse($info['already_mine']);
        $this->assertStringContainsString('نماینده الف', $info['message']);
    }

    public function test_transfer_requires_valid_otp_then_moves_customer(): void
    {
        $owner = User::factory()->create(['is_representative' => true, 'status' => true]);
        $target = User::factory()->create(['is_representative' => true, 'status' => true]);

        $province = Province::query()->firstOrFail();
        $city = $province->cities()->firstOrFail();

        $customer = app(RepresentativeCustomerService::class)->create($owner, [
            'name' => 'مشتری انتقال',
            'phone' => '09123334455',
            'national_code' => '0067749828',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس',
        ]);

        $service = app(RepresentativeCustomerService::class);
        $service->sendTransferOtp($target, $customer);

        $this->expectException(ValidationException::class);
        try {
            $service->confirmTransfer($target, $customer, '000000');
        } catch (ValidationException $e) {
            $this->assertSame($owner->id, $customer->fresh()->created_by_representative_id);
            throw $e;
        }
    }

    public function test_transfer_succeeds_with_cached_otp(): void
    {
        $owner = User::factory()->create(['is_representative' => true, 'status' => true]);
        $target = User::factory()->create(['is_representative' => true, 'status' => true]);

        $province = Province::query()->firstOrFail();
        $city = $province->cities()->firstOrFail();

        $customer = app(RepresentativeCustomerService::class)->create($owner, [
            'name' => 'مشتری انتقال ۲',
            'phone' => '09125556677',
            'national_code' => '0013542419',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس',
        ]);

        $service = app(RepresentativeCustomerService::class);
        $service->sendTransferOtp($target, $customer);

        $cacheKey = 'otp:'.RepresentativeCustomerService::TRANSFER_OTP_PURPOSE.':09125556677';
        $payload = Cache::get($cacheKey);
        $this->assertIsArray($payload);
        $code = (string) $payload['code'];

        $transferred = $service->confirmTransfer($target, $customer, $code);

        $this->assertSame($target->id, (int) $transferred->created_by_representative_id);
        $this->assertNull(Cache::get($cacheKey));
    }
}
