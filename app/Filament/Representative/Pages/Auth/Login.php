<?php

namespace App\Filament\Representative\Pages\Auth;

use App\Filament\Pages\Auth\Login as AdminLogin;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\RepresentativeLoginGuard;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends AdminLogin
{
    public function sendAdminOtp(OtpService $otp): void
    {
        $this->activeLoginTab = 'mobile';
        $this->otpPhone = $otp->normalizePhone($this->otpPhone);

        $this->validate([
            'otpPhone' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'otpPhone.required' => 'لطفا شماره موبایل خود را وارد کنید.',
            'otpPhone.regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.',
        ]);

        $user = User::query()->where('phone', $this->otpPhone)->first();
        app(RepresentativeLoginGuard::class)->assertAllowed($user, 'otpPhone');

        $key = 'representative-otp-send:'.$this->otpPhone;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'otpPhone' => 'تلاش‌های زیاد. لطفاً بعداً دوباره امتحان کنید.',
            ]);
        }

        if ($otp->resendCooldownRemainingSeconds($this->otpPhone) > 0) {
            $this->advanceAdminToOtpIfPending($otp);

            return;
        }

        try {
            $otp->send($this->otpPhone);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'otpPhone' => $e->getMessage(),
                'otpCode' => $e->getMessage(),
            ]);
        }

        RateLimiter::hit($key, 300);
        $this->otpStep = 'otp';
        $this->otpCode = '';
        $this->otpSentAt = time();
    }

    public function verifyAdminOtp(OtpService $otp): ?LoginResponse
    {
        $this->activeLoginTab = 'mobile';

        $this->validate([
            'otpPhone' => ['required', 'regex:/^09\d{9}$/'],
            'otpCode' => ['required', 'digits:'.config('shop.otp.length')],
        ], [
            'otpCode.required' => 'لطفا کد تایید را وارد کنید.',
            'otpCode.digits' => 'کد تایید باید ۶ رقم باشد.',
        ]);

        $key = 'representative-otp-verify:'.$this->otpPhone;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'otpCode' => 'تلاش‌های زیاد. لطفاً کد جدید درخواست کنید.',
            ]);
        }

        if (! $otp->verify($this->otpPhone, $this->otpCode)) {
            RateLimiter::hit($key, 120);
            throw ValidationException::withMessages([
                'otpCode' => 'کد تایید نامعتبر یا منقضی شده است.',
            ]);
        }

        RateLimiter::clear($key);

        $user = User::query()->where('phone', $this->otpPhone)->first();
        app(RepresentativeLoginGuard::class)->assertAllowed($user, 'otpCode');

        Filament::auth()->login($user, remember: true);

        if (
            ($user instanceof FilamentUser)
            && (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'otpCode' => 'امکان ورود نمایندگی با این شماره وجود ندارد.',
            ]);
        }

        $otp->markPhoneVerified($user);

        $user->forceFill([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
        ])->save();

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function authenticate(): ?LoginResponse
    {
        $this->activeLoginTab = 'username';

        $response = parent::authenticate();

        if ($response !== null) {
            $user = Filament::auth()->user();
            if ($user instanceof User) {
                app(RepresentativeLoginGuard::class)->assertAllowed($user, 'data.email');
            }
        }

        return $response;
    }
}
