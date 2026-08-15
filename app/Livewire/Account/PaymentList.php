<?php

namespace App\Livewire\Account;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.shop')]
#[Title('پرداخت‌ها')]
class PaymentList extends Component
{
    use WithPagination;

    public function render()
    {
        $payments = auth()->user()
            ->payments()
            ->with('order')
            ->latest()
            ->paginate(10);

        return view('livewire.account.payment-list', compact('payments'));
    }
}
