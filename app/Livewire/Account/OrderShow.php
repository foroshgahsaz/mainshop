<?php

namespace App\Livewire\Account;

use App\Models\Order;
use App\Models\OrderNote;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Payment\PaymentService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class OrderShow extends Component
{
    public Order $order;

    public string $selectedGateway = '';

    public function mount(Order $order, PaymentGatewayCatalog $catalog): void
    {
        $this->authorize('view', $order);
        $this->order = $order->load([
            'items.product',
            'address',
            'shippingMethod',
            'freightCarrier.province',
            'freightCarrier.city',
            'payments',
            'coupon',
            'notes' => fn ($q) => $q->where('type', OrderNote::TYPE_CUSTOMER)->with('author'),
        ]);

        $proformaGateway = $this->order->proformaPaymentGateway();
        if ($proformaGateway !== null && $catalog->isEnabled($proformaGateway)) {
            $this->selectedGateway = $proformaGateway;
        } else {
            $this->selectedGateway = $this->defaultGateway($catalog);
        }
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

    public function payAgain(PaymentService $payments, PaymentGatewayCatalog $catalog): void
    {
        if (! $this->order->canInitiateOnlinePayment()) {
            session()->flash('error', 'این سفارش قابل پرداخت نیست.');

            return;
        }

        $gateway = $this->order->proformaPaymentGateway();

        if ($gateway === null) {
            $this->validate([
                'selectedGateway' => ['required', Rule::in($catalog->enabledNames())],
            ], [
                'selectedGateway.required' => 'درگاه پرداخت را انتخاب کنید.',
                'selectedGateway.in' => 'درگاه پرداخت معتبر نیست.',
            ]);

            $gateway = $this->selectedGateway;
        }

        try {
            $payment = $payments->createForOrder($this->order, $gateway);
            $url = $payments->initiate($payment, $this->order);
            $this->redirect($url);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(PaymentGatewayCatalog $catalog)
    {
        return view('livewire.account.order-show', [
            'creditGateways' => $catalog->credit(),
            'cashGateways' => $catalog->cash(),
        ]);
    }

    protected function defaultGateway(PaymentGatewayCatalog $catalog): string
    {
        $cash = $catalog->cash();
        if ($cash !== []) {
            return $cash[0]['name'];
        }

        $credit = $catalog->credit();
        if ($credit !== []) {
            return $credit[0]['name'];
        }

        return '';
    }
}
