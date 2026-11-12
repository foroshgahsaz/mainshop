<?php

namespace App\Services\Representative;

use App\Contracts\SmsSender;
use App\Models\City;
use App\Models\OtpMessage;
use App\Models\Province;
use App\Models\User;
use App\Models\UserAddress;
use App\Rules\IranianNationalCode;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RepresentativeCustomerService
{
    public const TRANSFER_OTP_PURPOSE = 'customer-transfer';

    /**
     * @return array{
     *     customer: User,
     *     representative: ?User,
     *     representative_name: string,
     *     already_mine: bool,
     *     message: string
     * }|null
     */
    public function lookupByNationalCode(string $nationalCode, User $actingRepresentative): ?array
    {
        $nationalCode = IranianNationalCode::normalize($nationalCode);

        if ($nationalCode === '' || strlen($nationalCode) !== 10) {
            return null;
        }

        $customer = User::query()
            ->with('createdByRepresentative:id,name')
            ->where('national_code', $nationalCode)
            ->where('is_admin', false)
            ->where('is_author', false)
            ->where('is_representative', false)
            ->where('is_sales_manager', false)
            ->where('is_bankdar', false)
            ->first();

        if ($customer === null) {
            return null;
        }

        $owner = $customer->createdByRepresentative;
        $alreadyMine = (int) $customer->created_by_representative_id === (int) $actingRepresentative->id;
        $ownerName = $owner?->name ?: 'بدون نمایندگی مشخص';

        $message = $alreadyMine
            ? 'این مشتری هم‌اکنون زیر نمایندگی شما ثبت شده است.'
            : "این مشتری در نمایندگی «{$ownerName}» موجود است. برای انتقال به نمایندگی خود، دکمه انتقال را بزنید تا کد تأیید برای مشتری ارسال شود.";

        return [
            'customer' => $customer,
            'representative' => $owner,
            'representative_name' => $ownerName,
            'already_mine' => $alreadyMine,
            'message' => $message,
        ];
    }

    public function sendTransferOtp(User $actingRepresentative, User $customer): void
    {
        $this->assertTransferable($actingRepresentative, $customer);

        $phone = app(OtpService::class)->normalizePhone((string) $customer->phone);

        if (! preg_match('/^09\d{9}$/', $phone)) {
            throw ValidationException::withMessages([
                'national_code' => 'شماره موبایل مشتری برای ارسال کد تأیید معتبر نیست.',
            ]);
        }

        $throttleKey = $this->transferThrottleKey($phone);
        $remaining = $this->transferCooldownRemaining($phone);
        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'national_code' => "ارسال مجدد تا {$remaining} ثانیه دیگر فعال نیست.",
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), (int) config('shop.otp.length', 6), '0', STR_PAD_LEFT);

        Cache::put(
            $this->transferCacheKey($phone),
            [
                'code' => $code,
                'customer_id' => (int) $customer->id,
                'to_representative_id' => (int) $actingRepresentative->id,
            ],
            now()->addMinutes((int) config('shop.otp.expires_minutes', 5))
        );

        $cooldown = (int) config('shop.otp.throttle_seconds', 60);
        Cache::put($throttleKey, now()->addSeconds($cooldown)->getTimestamp(), now()->addSeconds($cooldown));

        app(SmsSender::class)->sendOtp($phone, $code);
        $this->recordTransferOtp($phone, $code, $customer);
    }

    public function confirmTransfer(User $actingRepresentative, User $customer, string $code): User
    {
        $this->assertTransferable($actingRepresentative, $customer);

        $phone = app(OtpService::class)->normalizePhone((string) $customer->phone);
        $payload = Cache::get($this->transferCacheKey($phone));

        if (! is_array($payload)
            || ! hash_equals((string) ($payload['code'] ?? ''), trim($code))
            || (int) ($payload['customer_id'] ?? 0) !== (int) $customer->id
            || (int) ($payload['to_representative_id'] ?? 0) !== (int) $actingRepresentative->id
        ) {
            throw ValidationException::withMessages([
                'transfer_otp' => 'کد تأیید نامعتبر یا منقضی شده است.',
            ]);
        }

        Cache::forget($this->transferCacheKey($phone));

        $customer->forceFill([
            'created_by_representative_id' => $actingRepresentative->id,
        ])->save();

        return $customer->fresh();
    }

    protected function assertTransferable(User $actingRepresentative, User $customer): void
    {
        if (! $actingRepresentative->is_representative || $actingRepresentative->isAdmin()) {
            throw ValidationException::withMessages([
                'national_code' => 'فقط نماینده می‌تواند مشتری را منتقل کند.',
            ]);
        }

        if ($customer->is_admin || $customer->is_representative || $customer->is_sales_manager || $customer->is_bankdar || $customer->is_author) {
            throw ValidationException::withMessages([
                'national_code' => 'این کاربر مشتری قابل انتقال نیست.',
            ]);
        }

        if ((int) $customer->created_by_representative_id === (int) $actingRepresentative->id) {
            throw ValidationException::withMessages([
                'national_code' => 'این مشتری هم‌اکنون متعلق به شماست.',
            ]);
        }
    }

    protected function transferCacheKey(string $phone): string
    {
        return 'otp:'.self::TRANSFER_OTP_PURPOSE.':'.Str::slug($phone);
    }

    protected function transferThrottleKey(string $phone): string
    {
        return 'otp:throttle:'.self::TRANSFER_OTP_PURPOSE.':'.Str::slug($phone);
    }

    public function transferCooldownRemaining(string $phone): int
    {
        $phone = app(OtpService::class)->normalizePhone($phone);
        $expiresAt = Cache::get($this->transferThrottleKey($phone));

        if (! is_numeric($expiresAt)) {
            return 0;
        }

        return max(0, (int) $expiresAt - time());
    }

    protected function recordTransferOtp(string $phone, string $code, User $customer): void
    {
        try {
            if (! Schema::hasTable('otp_messages')) {
                return;
            }

            OtpMessage::query()->create([
                'phone' => $phone,
                'code' => $code,
                'user_id' => $customer->id,
                'user_name' => $customer->name,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array{name: string, phone: string, national_code: string, province_id: int, city_id: int, address: string, postal_code?: ?string}  $data
     */
    public function create(User $representative, array $data): User
    {
        $nationalCode = IranianNationalCode::normalize((string) ($data['national_code'] ?? ''));

        $existingByCode = $this->lookupByNationalCode($nationalCode, $representative);
        if ($existingByCode !== null) {
            throw ValidationException::withMessages([
                'national_code' => $existingByCode['message'],
            ]);
        }

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
