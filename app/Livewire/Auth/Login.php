<?php

namespace App\Livewire\Auth;

use App\Livewire\Auth\Concerns\HandlesOtpLogin;
use App\Livewire\Auth\Concerns\HandlesPasswordLogin;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('ورود | ثبت‌نام')]
class Login extends Component
{
    use HandlesOtpLogin;
    use HandlesPasswordLogin;

    public string $activeTab = 'otp';

    public function mount(): void
    {
        $redirect = request()->query('redirect');

        if (! is_string($redirect) || $redirect === '' || str_contains($redirect, '/login')) {
            return;
        }

        $target = parse_url($redirect, PHP_URL_SCHEME) ? $redirect : url($redirect);
        session(['url.intended' => $target]);
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

    public function render()
    {
        return view('livewire.auth.login');
    }
}
