<?php

namespace App\Livewire\Auth;

use App\Livewire\Auth\Concerns\HandlesOtpLogin;
use App\Livewire\Auth\Concerns\HandlesPasswordLogin;
use App\Models\User;
use App\Support\UserDisplayName;
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

        if ($this->shouldOpenProfileStepForAuthenticatedUser()) {
            $this->prepareProfileStepForAuthenticatedUser();
        } else {
            $this->resetLoginForm();
        }

        $this->js('toggleElement("loginModal", true)');
    }

    public function closeModal(): void
    {
        if ($this->mustCompleteProfileBeforeClose()) {
            $this->addError('firstName', 'لطفاً نام و نام خانوادگی را وارد کنید تا ادامه دهید.');

            return;
        }

        $this->resetLoginForm();
        $this->js('toggleElement("loginModal", false)');
    }

    protected function finalizeShopLogin(User $user): void
    {
        $this->resetLoginForm();
        $this->js('toggleElement("loginModal", false)');

        $this->redirectAfterShopLogin($user);
    }

    protected function onProfileStepRequested(): void
    {
        $this->js('toggleElement("loginModal", true)');
    }

    protected function resetLoginForm(): void
    {
        $this->activeTab = 'otp';
        $this->step = 'phone';
        $this->phone = '';
        $this->otp = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->pendingNewAccount = false;
        $this->resetPasswordForm();
        $this->resetValidation();
    }

    protected function shouldOpenProfileStepForAuthenticatedUser(): bool
    {
        $user = Auth::user();

        return $user !== null && UserDisplayName::isPlaceholder($user->name);
    }

    protected function prepareProfileStepForAuthenticatedUser(): void
    {
        $user = Auth::user();

        $this->activeTab = 'otp';
        $this->step = 'profile';
        $this->phone = (string) ($user?->phone ?? '');
        $this->otp = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->pendingNewAccount = false;
        $this->resetPasswordForm();
        $this->resetValidation();
    }

    protected function mustCompleteProfileBeforeClose(): bool
    {
        if ($this->step !== 'profile') {
            return false;
        }

        $user = Auth::user();

        return $user !== null && UserDisplayName::isPlaceholder($user->name);
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
