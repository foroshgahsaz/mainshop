<?php

namespace Tests\Unit;

use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OtpServiceResendCooldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_cooldown_uses_expiry_timestamp(): void
    {
        $otp = app(OtpService::class);
        $phone = '09121112222';

        Cache::put('otp:throttle:'.$phone, now()->addSeconds(90)->getTimestamp(), 120);

        $remaining = $otp->resendCooldownRemainingSeconds($phone);

        $this->assertGreaterThan(80, $remaining);
        $this->assertLessThanOrEqual(90, $remaining);
    }

    public function test_normalize_phone_converts_persian_digits_for_cooldown_lookup(): void
    {
        $otp = app(OtpService::class);
        $asciiPhone = '09123334444';
        $persianPhone = '۰۹۱۲۳۳۳۴۴۴۴';

        Cache::put('otp:throttle:'.$asciiPhone, now()->addSeconds(60)->getTimestamp(), 120);

        $this->assertGreaterThan(0, $otp->resendCooldownRemainingSeconds($persianPhone));
    }

    public function test_has_pending_otp_when_code_in_cache(): void
    {
        $otp = app(OtpService::class);
        $phone = '09126667777';

        Cache::put('otp:phone:'.'09126667777', '123456', 300);

        $this->assertTrue($otp->hasPendingOtp($phone));
    }

    public function test_generate_throws_while_cooldown_active(): void
    {
        $otp = app(OtpService::class);
        $phone = '09124445555';

        Cache::put('otp:throttle:'.$phone, now()->addSeconds(30)->getTimestamp(), 60);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ارسال مجدد هنوز فعال نیست');

        $otp->generate($phone);
    }
}
