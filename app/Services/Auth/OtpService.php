<?php

namespace App\Services\Auth;

use App\Contracts\SmsSender;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    public function __construct(
        protected SmsSender $sms,
        protected SettingsService $settings
    ) {}

    public function send(string $phone): void
    {
        $phone = $this->normalizePhone($phone);
        $code = $this->generate($phone);
        $this->sms->sendOtp($phone, $code);
    }

    public function generate(string $phone): string
    {
        $phone = $this->normalizePhone($phone);
        $throttleKey = "otp:throttle:{$phone}";

        if ($this->resendCooldownRemainingSeconds($phone) > 0) {
            throw new \RuntimeException('ارسال مجدد هنوز فعال نیست. تا پایان شمارنده صبر کنید.');
        }

        $code = str_pad((string) random_int(0, 999999), config('shop.otp.length'), '0', STR_PAD_LEFT);

        Cache::put(
            $this->cacheKey($phone),
            $code,
            now()->addMinutes(config('shop.otp.expires_minutes'))
        );

        $cooldownSeconds = $this->settings->otpResendSeconds();
        $expiresAt = now()->addSeconds($cooldownSeconds)->getTimestamp();

        Cache::put($throttleKey, $expiresAt, now()->addSeconds($cooldownSeconds));

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        $phone = $this->normalizePhone($phone);
        $cached = Cache::get($this->cacheKey($phone));

        if ($cached === null || ! hash_equals((string) $cached, $code)) {
            return false;
        }

        Cache::forget($this->cacheKey($phone));

        return true;
    }

    public function markPhoneVerified(User $user): void
    {
        $user->forceFill(['phone_verified_at' => now()])->save();
    }

    public function resendCooldownRemainingSeconds(string $phone): int
    {
        $phone = $this->normalizePhone($phone);

        if ($phone === '') {
            return 0;
        }

        $expiresAt = Cache::get("otp:throttle:{$phone}");

        if ($expiresAt === true) {
            return $this->settings->otpResendSeconds();
        }

        if (! is_numeric($expiresAt)) {
            return 0;
        }

        return max(0, (int) $expiresAt - time());
    }

    public function normalizePhone(string $phone): string
    {
        $phone = str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            trim($phone)
        );

        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    protected function cacheKey(string $phone): string
    {
        return 'otp:phone:'.Str::slug($phone);
    }
}
