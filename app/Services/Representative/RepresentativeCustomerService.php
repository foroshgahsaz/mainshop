<?php

namespace App\Services\Representative;

use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Models\UserAddress;
use App\Rules\IranianNationalCode;
use App\Services\Auth\OtpService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepresentativeCustomerService
{
    /**
     * @param  array{name: string, phone: string, national_code: string, province_id: int, city_id: int, address: string, postal_code?: ?string}  $data
     */
    public function create(User $representative, array $data): User
    {
        $nationalCode = IranianNationalCode::normalize((string) ($data['national_code'] ?? ''));

        validator(
            ['national_code' => $nationalCode],
            [
                'national_code' => [
                    'required',
                    new IranianNationalCode,
                    Rule::unique('users', 'national_code'),
                ],
            ],
            [
                'national_code.required' => 'کد ملی الزامی است.',
                'national_code.unique' => 'مشتری با این کد ملی قبلاً ثبت شده است.',
            ]
        )->validate();

        $phone = app(OtpService::class)->normalizePhone($data['phone']);

        if (! preg_match('/^09\d{9}$/', $phone)) {
            throw ValidationException::withMessages([
                'phone' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.',
            ]);
        }

        $existing = User::query()->where('phone', $phone)->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'phone' => 'مشتری با این شماره موبایل موجود است.',
            ]);
        }

        $provinceId = (int) ($data['province_id'] ?? 0);
        $cityId = (int) ($data['city_id'] ?? 0);
        $addressLine = trim((string) ($data['address'] ?? ''));

        validator(
            [
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'address' => $addressLine,
            ],
            [
                'province_id' => ['required', 'integer', Rule::exists('provinces', 'id')],
                'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
                'address' => ['required', 'string', 'max:1000'],
            ],
            [
                'province_id.required' => 'استان را انتخاب کنید.',
                'city_id.required' => 'شهر را انتخاب کنید.',
                'address.required' => 'آدرس را وارد کنید.',
            ]
        )->validate();

        $city = City::query()->find($cityId);

        if ($city === null || (int) $city->province_id !== $provinceId) {
            throw ValidationException::withMessages([
                'city_id' => 'شهر انتخاب‌شده با استان هم‌خوانی ندارد.',
            ]);
        }

        $provinceName = Province::query()->find($provinceId)?->name ?? '';
        $cityName = $city->name;

        return DB::transaction(function () use ($representative, $data, $phone, $nationalCode, $provinceId, $cityId, $addressLine, $provinceName, $cityName): User {
            $customer = User::query()->create([
                'name' => $data['name'],
                'phone' => $phone,
                'national_code' => $nationalCode,
                'password' => Hash::make(Str::random(32)),
                'status' => true,
                'is_admin' => false,
                'is_author' => false,
                'is_representative' => false,
                'created_by_representative_id' => $representative->id,
            ]);

            UserAddress::query()->create([
                'user_id' => $customer->id,
                'receiver_name' => $data['name'],
                'receiver_phone' => $phone,
                'province_id' => $provinceId,
                'city_id' => $cityId,
                'province' => $provinceName,
                'city' => $cityName,
                'address' => $addressLine,
                'postal_code' => $data['postal_code'] ?? null,
                'is_default' => true,
            ]);

            return $customer;
        });
    }
}
