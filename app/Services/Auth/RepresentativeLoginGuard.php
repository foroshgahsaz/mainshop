<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class RepresentativeLoginGuard
{
    public function assertAllowed(?User $user, string $field = 'otpPhone'): void
    {
        if ($user === null || ! $user->isRepresentative() || ! $user->status) {
            throw ValidationException::withMessages([
                $field => 'امکان ورود نمایندگی با این حساب وجود ندارد.',
            ]);
        }
    }
}
