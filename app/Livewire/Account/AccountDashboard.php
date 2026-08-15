<?php

namespace App\Livewire\Account;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('داشبورد')]
class AccountDashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        $ordersCount = $user->orders()->count();
        $pendingOrders = $user->orders()->whereIn('status', ['pending', 'processing'])->count();
        $paymentsCount = $user->payments()->where('status', 'success')->count();
        $recentOrders = $user->orders()->latest()->limit(5)->get();

        return view('livewire.account.account-dashboard', compact(
            'ordersCount',
            'pendingOrders',
            'paymentsCount',
            'recentOrders',
        ));
    }
}
