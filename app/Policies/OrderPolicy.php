<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id || $user->isAdmin();
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return $order->canBeCanceledByAdmin();
        }

        return $user->id === $order->user_id && $order->canBeCanceledByCustomer();
    }
}
