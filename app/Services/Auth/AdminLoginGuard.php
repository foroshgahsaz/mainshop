<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminLoginGuard
{
    public function canAccessAdminLogin(?User $user): bool
    {
        if ($user === null || ! $user->status) {
            return false;
        }

        return $user->is_admin || $user->isSalesManager();
    }

    public function assertAllowed(?User $user, string $field = 'otpPhone'): void
    {
        if (! $this->canAccessAdminLogin($user)) {
            throw ValidationException::withMessages([
                $field => 'امکان ورود مدیریت با این شماره وجود ندارد.',
            ]);
        }
    }
}
