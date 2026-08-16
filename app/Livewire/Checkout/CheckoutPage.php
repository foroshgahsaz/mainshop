<?php

namespace App\Livewire\Checkout;

use App\Services\Cache\ShopCacheService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\PaymentGatewayCatalog;
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

    public string $paymentMethod = 'zarinpal';

    public ?string $note = null;

    public function mount(PaymentGatewayCatalog $catalog): void
    {
        if (! auth()->check()) {
            redirect()->setIntendedUrl(route('checkout'));
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->paymentMethod = $this->defaultPaymentMethod($catalog);
    }

    public function placeOrder(CheckoutService $checkout, PaymentService $payment, PaymentGatewayCatalog $catalog): void
    {
        $this->validate($this->checkoutRules($catalog));

        $isCod = $this->paymentMethod === 'cod';

        try {
            $order = $checkout->placeOrder(
                auth()->user(),
                $this->addressId,
                $this->shippingMethodId,
                $this->couponCode ?: null,
                $isCod ? 'cod' : 'online',
                $this->note
            );
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        if (! $isCod) {
            try {
                $paymentModel = $payment->createForOrder($order, $this->paymentMethod);
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

    public function render(CheckoutService $checkout, ShopCacheService $cache, PaymentGatewayCatalog $catalog)
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
            'creditGateways' => $catalog->credit(),
            'cashGateways' => $catalog->cash(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function checkoutRules(PaymentGatewayCatalog $catalog): array
    {
        $allowed = array_merge(['cod'], $catalog->enabledNames());

        $rules = [
            'addressId' => [
                'required',
                'integer',
                Rule::exists('user_addresses', 'id')->where('user_id', auth()->id()),
            ],
            'paymentMethod' => ['required', Rule::in($allowed)],
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
            'paymentMethod.required' => 'روش پرداخت را انتخاب کنید.',
            'paymentMethod.in' => 'روش پرداخت معتبر نیست.',
        ];
    }

    protected function defaultPaymentMethod(PaymentGatewayCatalog $catalog): string
    {
        $cash = $catalog->cash();
        if ($cash !== []) {
            return $cash[0]['name'];
        }

        $credit = $catalog->credit();
        if ($credit !== []) {
            return $credit[0]['name'];
        }

        return 'cod';
    }
}
