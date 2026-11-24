<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->id === $payment->user_id) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAccountant()) {
            return true;
        }

        if ($user->isSalesManager() || $user->isBankdar()) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
