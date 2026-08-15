<?php

namespace App\Livewire\Layout;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HeaderAuth extends Component
{
    public string $variant = 'desktop';

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
