<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->id === $order->user_id) {
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

    public function cancel(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return $order->canBeCanceledByAdmin();
        }

        return $user->id === $order->user_id && $order->canBeCanceledByCustomer();
    }
}
