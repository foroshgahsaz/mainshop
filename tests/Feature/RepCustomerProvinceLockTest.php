<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Province;
use App\Models\RepresentativeProfile;
use App\Models\User;
use App\Services\Representative\RepresentativeCustomerService;
use App\Services\Representative\SalesRegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RepCustomerProvinceLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_province_comes_from_profile_then_bankdar(): void
    {
        $province = Province::query()->forceCreate([
            'name' => 'قم',
            'slug' => 'qom-test',
            'position' => 1,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'بنکدار قم',
            'phone' => '09120001101',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
            'bankdar_province_id' => $province->id,
        ]);

        $rep = User::query()->forceCreate([
            'name' => 'نماینده قم',
            'phone' => '09120001102',
            'password' => bcrypt('x'),
            'status' => true,
            'is_representative' => true,
        ]);

        RepresentativeProfile::query()->forceCreate([
            'user_id' => $rep->id,
            'sales_manager_id' => $bankdar->id,
            'province_id' => $province->id,
        ]);

        $svc = app(SalesRegionService::class);
        $this->assertSame((int) $province->id, $svc->activityProvinceIdForRepresentative($rep));
    }

    public function test_create_customer_forces_locked_province(): void
    {
        $qom = Province::query()->forceCreate([
            'name' => 'قم',
            'slug' => 'qom-lock',
            'position' => 1,
        ]);
        $tehran = Province::query()->forceCreate([
            'name' => 'تهران',
            'slug' => 'tehran-lock',
            'position' => 2,
        ]);
        $qomCity = City::query()->forceCreate([
            'name' => 'قم',
            'slug' => 'qom-city',
            'province_id' => $qom->id,
            'position' => 1,
        ]);
        $tehranCity = City::query()->forceCreate([
            'name' => 'تهران',
            'slug' => 'tehran-city',
            'province_id' => $tehran->id,
            'position' => 1,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'بنکدار',
            'phone' => '09120002201',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
            'bankdar_province_id' => $qom->id,
        ]);
        $rep = User::query()->forceCreate([
            'name' => 'نماینده',
            'phone' => '09120002202',
            'password' => bcrypt('x'),
            'status' => true,
            'is_representative' => true,
        ]);
        RepresentativeProfile::query()->forceCreate([
            'user_id' => $rep->id,
            'sales_manager_id' => $bankdar->id,
            'province_id' => $qom->id,
        ]);

        $customer = app(RepresentativeCustomerService::class)->create($rep, [
            'name' => 'مشتری',
            'phone' => '09123334455',
            'national_code' => '0013542419',
            'province_id' => $tehran->id, // تلاش برای استان دیگر
            'city_id' => $qomCity->id,
            'address' => 'خیابان تست',
        ]);

        $address = $customer->addresses()->first();
        $this->assertNotNull($address);
        $this->assertSame((int) $qom->id, (int) $address->province_id);
        $this->assertSame('قم', $address->province);

        try {
            app(RepresentativeCustomerService::class)->create($rep, [
                'name' => 'مشتری ۲',
                'phone' => '09123334456',
                'national_code' => '0067749828',
                'province_id' => $tehran->id,
                'city_id' => $tehranCity->id,
                'address' => 'آدرس تهران',
            ]);
            $this->fail('Expected city/province mismatch when city is outside locked province');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('city_id', $e->errors());
        }
    }
}
