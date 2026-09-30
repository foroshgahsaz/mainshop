<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderActivityLogger;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Payment\PaymentService;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RepresentativeProformaPaymentService
{
    public function __construct(
        protected PaymentService $payments,
        protected PaymentGatewayCatalog $catalog,
        protected OrderActivityLogger $orderLog,
    ) {}

    public function initiateGatewayPayment(Order $order, User $representative): string
    {
        $this->assertCanPay($order, $representative);

        $gateway = (string) $order->payment_method;
        $this->catalog->assertEnabled($gateway);

        $payment = $this->payments->createForOrder($order, $gateway, $representative->id);

        $this->orderLog->system(
            $order,
            'نماینده '.$representative->name.' درگاه پرداخت را برای مشتری '.$order->user?->name.' باز کرد.',
            'rep_payment_initiated'
        );

        return $this->payments->initiate($payment, $order->fresh(['user', 'representative']));
    }

    public function assertCanPay(Order $order, User $representative): void
    {
        if ($order->representative_id !== $representative->id) {
            throw ValidationException::withMessages([
                'order' => 'این پیش‌فاکتور متعلق به نمایندگی شما نیست.',
            ]);
        }

        if (! $order->canRepresentativePayProforma()) {
            throw ValidationException::withMessages([
                'order' => 'این پیش‌فاکتور در وضعیت فعلی قابل پرداخت نیست.',
            ]);
        }
    }

    public function assertCanPayOrFail(Order $order, User $representative): void
    {
        try {
            $this->assertCanPay($order, $representative);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'پرداخت مجاز نیست.';
            throw new RuntimeException($message);
        }
    }
}
