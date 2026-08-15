<?php

namespace App\Livewire\Account;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.shop')]
#[Title('سفارش‌های من')]
class OrderList extends Component
{
    use WithPagination;

    public function render()
    {
        $orders = auth()->user()
            ->orders()
            ->with(['items', 'latestPayment'])
            ->latest()
            ->paginate(10);

        return view('livewire.account.order-list', compact('orders'));
    }
}
