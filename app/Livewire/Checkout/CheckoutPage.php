<?php

namespace App\Livewire\Checkout;

use App\Services\Cache\ShopCacheService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('تسویه حساب')]
class CheckoutPage extends Component
{
    public ?int $addressId = null;

    public ?int $shippingMethodId = null;

    public string $couponCode = '';

    public string $paymentMethod = 'online';

    public ?string $note = null;

    public function mount(): void
    {
        if (! auth()->check()) {
            redirect()->setIntendedUrl(route('checkout'));
            $this->redirect(route('login'), navigate: true);
        }
    }

    public function placeOrder(CheckoutService $checkout, PaymentService $payment): void
    {
        $this->validate($this->checkoutRules());

        try {
            $order = $checkout->placeOrder(
                auth()->user(),
                $this->addressId,
                $this->shippingMethodId,
                $this->couponCode ?: null,
                $this->paymentMethod,
                $this->note
            );
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        if ($order->payment_method === 'online') {
            try {
                $paymentModel = $payment->createForOrder($order);
                $url = $payment->initiate($paymentModel, $order);
                $this->redirect($url);
            } catch (\RuntimeException $e) {
                session()->flash('error', $e->getMessage());
                $this->redirect(route('account.orders.show', $order), navigate: true);
            }

            return;
        }

        $this->redirect(route('account.orders.show', $order), navigate: true);
    }

    public function render(CheckoutService $checkout, ShopCacheService $cache)
    {
        $preview = null;
        $error = null;

        try {
            $preview = $checkout->preview(
                auth()->user(),
                $this->couponCode ?: null,
                $this->shippingMethodId
            );
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
        }

        return view('livewire.checkout.checkout-page', [
            'preview' => $preview,
            'error' => $error,
            'addresses' => auth()->user()->addresses,
            'shippingMethods' => $cache->shippingMethods(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function checkoutRules(): array
    {
        $rules = [
            'addressId' => [
                'required',
                'integer',
                Rule::exists('user_addresses', 'id')->where('user_id', auth()->id()),
            ],
            'paymentMethod' => ['in:online,cod'],
        ];

        if (app(CheckoutService::class)->hasActiveShippingMethods()) {
            $rules['shippingMethodId'] = [
                'required',
                'integer',
                Rule::exists('shipping_methods', 'id')->where('is_active', true),
            ];
        } else {
            $rules['shippingMethodId'] = ['nullable', 'integer'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'addressId.required' => 'آدرس را انتخاب کنید.',
            'addressId.exists' => 'آدرس انتخاب‌شده معتبر نیست.',
            'shippingMethodId.required' => 'روش ارسال را انتخاب کنید.',
            'shippingMethodId.exists' => 'روش ارسال معتبر نیست.',
        ];
    }
}
