<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class ShopLoginGuard
{
    public function assertAllowed(?User $user, string $field = 'phone'): void
    {
        if ($user === null) {
            return;
        }

        if ($user->is_admin) {
            throw ValidationException::withMessages([
                $field => 'ورود مدیران فقط از مسیر /admin امکان‌پذیر است.',
            ]);
        }

        if (! $user->status) {
            throw ValidationException::withMessages([
                $field => 'حساب کاربری غیرفعال است.',
            ]);
        }
    }
}
