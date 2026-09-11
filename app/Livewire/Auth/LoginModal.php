<?php

namespace App\Livewire\Auth;

use App\Livewire\Auth\Concerns\HandlesOtpLogin;
use App\Livewire\Auth\Concerns\HandlesPasswordLogin;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class LoginModal extends Component
{
    use HandlesOtpLogin;
    use HandlesPasswordLogin;

    public string $activeTab = 'otp';

    #[On('open-login-modal')]
    public function openModal(?string $redirect = null): void
    {
        $this->rememberIntendedUrl($redirect);
        $this->resetLoginForm();
        $this->js('toggleElement("loginModal", true)');
    }

    public function closeModal(): void
    {
        $this->resetLoginForm();
        $this->js('toggleElement("loginModal", false)');
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
        $this->resetPasswordForm();
        $this->resetValidation();
    }

    protected function rememberIntendedUrl(?string $redirect): void
    {
        $target = $this->normalizeIntendedUrl($redirect ?: url()->current());

        if ($this->shouldSkipIntendedUrl($target)) {
            return;
        }

        session(['url.intended' => $target]);
    }

    protected function normalizeIntendedUrl(string $url): string
    {
        if (! parse_url($url, PHP_URL_SCHEME)) {
            return url($url);
        }

        return $url;
    }

    protected function shouldSkipIntendedUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';

        return in_array($path, ['/login', '/register'], true);
    }

    public function render()
    {
        return view('livewire.auth.login-modal');
    }
}
