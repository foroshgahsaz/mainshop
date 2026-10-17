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

    public function mount(): void
    {
        if ($this->shouldOpenProfileStepForAuthenticatedUser()) {
            $this->prepareProfileStepForAuthenticatedUser();
            $this->rememberIntendedUrl(url()->current());
        }
    }

    #[On('request-close-login-modal')]
    public function requestCloseFromBackdrop(): void
    {
        $this->closeModal();
    }

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
            $this->addError('profileName', 'لطفاً نام و نام خانوادگی و کد ملی را وارد کنید تا ادامه دهید.');

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

    protected function redirectAfterShopLogin(User $user): void
    {
        $default = app(\App\Services\Auth\LoginRedirectService::class)->shopDefaultUrl($user);

        if ($user->isRepresentative() && ! $user->isAdmin()) {
            session()->forget('url.intended');

            $this->redirect($default, navigate: false);

            return;
        }

        // Full reload so checkout/account pick up the saved display name (wire:navigate keeps stale Livewire state).
        $this->redirectIntended(default: $default, navigate: false);
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
        $this->profileName = '';
        $this->nationalCode = '';
        $this->pendingNewAccount = false;
        $this->profileUserId = null;
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
        $this->profileName = UserDisplayName::isPlaceholder($user?->name ?? '')
            ? ''
            : trim((string) ($user?->name ?? ''));
        $this->nationalCode = (string) ($user?->national_code ?? '');
        $this->pendingNewAccount = false;
        $this->profileUserId = $user !== null ? (int) $user->getKey() : null;
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
        return view('livewire.auth.login-modal', [
            'profileRequired' => $this->mustCompleteProfileBeforeClose(),
        ]);
    }
}
