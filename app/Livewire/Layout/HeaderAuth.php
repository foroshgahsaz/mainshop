<?php

namespace App\Livewire\Layout;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class HeaderAuth extends Component
{
    public string $variant = 'desktop';

    #[On('shop-user-profile-updated')]
    public function refreshUserLabel(): void
    {
        if (Auth::check()) {
            Auth::setUser(Auth::user()->fresh());
        }
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.layout.header-auth');
    }
}
