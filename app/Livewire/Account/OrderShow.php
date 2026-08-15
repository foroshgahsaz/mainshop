<?php

namespace App\Livewire\Account;

use App\Models\Order;
use App\Models\OrderNote;
use App\Models\Payment;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class OrderShow extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order->load([
            'items.product',
            'address',
            'shippingMethod',
            'payments',
            'coupon',
            'notes' => fn ($q) => $q->where('type', OrderNote::TYPE_CUSTOMER)->with('author'),
        ]);
    }

    public function cancel(OrderService $orderService): void
    {
        $this->authorize('cancel', $this->order);

        try {
            $this->order = $orderService->cancel($this->order, auth()->user());
            session()->flash('success', 'سفارش لغو شد.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function payAgain(PaymentService $payments): void
    {
        if (! $this->order->canPayAgain()) {
            session()->flash('error', 'این سفارش قابل پرداخت مجدد نیست.');

            return;
        }

        try {
            $payment = $payments->createForOrder($this->order);
            $url = $payments->initiate($payment, $this->order);
            $this->redirect($url);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.account.order-show');
    }
}
