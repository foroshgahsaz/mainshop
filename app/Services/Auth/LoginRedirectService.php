<?php

namespace App\Services\Auth;

use App\Models\User;
use Filament\Facades\Filament;

class LoginRedirectService
{
    public function shopDefaultUrl(User $user): string
    {
        if ($user->isRepresentative() && ! $user->isAdmin()) {
            return Filament::getPanel('representative')->getUrl();
        }

        return route('account.dashboard');
    }
}
