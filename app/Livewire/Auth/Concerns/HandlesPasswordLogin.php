<?php

namespace App\Livewire\Auth\Concerns;

use App\Models\User;
use App\Services\Auth\ShopLoginGuard;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait HandlesPasswordLogin
{
    public string $username = '';

    public string $password = '';

    public function loginWithPassword(CartService $cart): void
    {
        $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'نام کاربری یا شماره موبایل را وارد کنید.',
            'password.required' => 'رمز عبور را وارد کنید.',
        ]);

        $key = 'password-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'username' => 'تلاش‌های زیاد. لطفاً چند دقیقه بعد دوباره امتحان کنید.',
            ]);
        }

        $login = trim($this->username);

        $user = User::query()
            ->where(function ($query) use ($login) {
                $query->where('phone', $login)->orWhere('email', $login);
            })
            ->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages([
                'username' => 'نام کاربری یا رمز عبور اشتباه است.',
            ]);
        }

        app(ShopLoginGuard::class)->assertAllowed($user, 'username');

        RateLimiter::clear($key);

        $user->forceFill([
            'last_login_at' => now(),
            'login_count' => $user->login_count + 1,
        ])->save();

        $this->afterSuccessfulLogin($user, $cart);
    }

    protected function resetPasswordForm(): void
    {
        $this->username = '';
        $this->password = '';
    }
}
