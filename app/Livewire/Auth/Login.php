<?php

namespace App\Livewire\Auth;

use App\Livewire\Auth\Concerns\HandlesOtpLogin;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('ورود با کد یکبار مصرف')]
class Login extends Component
{
    use HandlesOtpLogin;

    public function render()
    {
        return view('livewire.auth.login');
    }
}
