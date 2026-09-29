<?php

namespace App\Services\Representative;

use App\Models\User;
use App\Models\UserAddress;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RepresentativeCustomerService
{
    /**
     * @param  array{name: string, phone: string, province_id: int, city_id: int, address: string, postal_code?: ?string}  $data
     */
    public function create(User $representative, array $data): User
    {
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

        return DB::transaction(function () use ($representative, $data, $phone): User {
            $customer = User::query()->create([
                'name' => $data['name'],
                'phone' => $phone,
                'password' => Hash::make(Str::random(32)),
                'status' => true,
                'is_admin' => false,
                'is_author' => false,
                'is_representative' => false,
                'created_by_representative_id' => $representative->id,
            ]);

            $provinceName = \App\Models\Province::query()->find($data['province_id'])?->name ?? '';
            $cityName = \App\Models\City::query()->find($data['city_id'])?->name ?? '';

            UserAddress::query()->create([
                'user_id' => $customer->id,
                'receiver_name' => $data['name'],
                'receiver_phone' => $phone,
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'province' => $provinceName,
                'city' => $cityName,
                'address' => $data['address'],
                'postal_code' => $data['postal_code'] ?? null,
                'is_default' => true,
            ]);

            return $customer;
        });
    }
}
