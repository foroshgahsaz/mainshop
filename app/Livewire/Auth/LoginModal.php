<?php

namespace App\Livewire\Auth;

use App\Livewire\Auth\Concerns\HandlesOtpLogin;
use App\Models\User;
use App\Services\Auth\ShopLoginGuard;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class LoginModal extends Component
{
    use HandlesOtpLogin;

    public string $activeTab = 'otp';

    public string $username = '';

    public string $password = '';

    #[On('open-login-modal')]
    public function openModal(): void
    {
        $this->resetLoginForm();
        $this->js('toggleElement("loginModal", true)');
    }

    public function closeModal(): void
    {
        $this->resetLoginForm();
        $this->js('toggleElement("loginModal", false)');
    }

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

    protected function afterSuccessfulLogin(User $user, CartService $cart): void
    {
        Auth::login($user, remember: true);
        $cart->mergeGuestCartIntoUser($user);

        $this->resetLoginForm();
        $this->js('toggleElement("loginModal", false)');

        $this->redirectIntended(default: route('account.dashboard'), navigate: true);
    }

    protected function resetLoginForm(): void
    {
        $this->activeTab = 'otp';
        $this->step = 'phone';
        $this->phone = '';
        $this->otp = '';
        $this->username = '';
        $this->password = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.auth.login-modal');
    }
}
