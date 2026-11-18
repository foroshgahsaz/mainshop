<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Rules\DarikMemberCode;
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
        protected RepresentativeProformaSplitPayment $split,
    ) {}

    public function initiateGatewayPayment(Order $order, User $representative): string
    {
        $this->assertCanPay($order, $representative);

        if ($this->split->usesSplitPlan($order)) {
            if ($this->split->remainingCashAmount($order) > 0) {
                return $this->initiateCashPayment($order, $representative, $this->split->remainingCashAmount($order));
            }

            if ($this->split->remainingCreditAmount($order) > 0) {
                return $this->initiateCreditPayment($order, $representative, $this->split->remainingCreditAmount($order));
            }
        }

        $gateway = (string) $order->payment_method;
        $this->catalog->assertEnabled($gateway);

        $payment = $this->payments->createForOrder($order, $gateway, $representative->id);
        $this->attachDarikMemberCodeIfNeeded($payment, $order->fresh('user'));

        $this->orderLog->system(
            $order,
            'نماینده '.$representative->name.' درگاه پرداخت را برای مشتری '.$order->user?->name.' باز کرد.',
            'rep_payment_initiated'
        );

        return $this->payments->initiate($payment->fresh(), $order->fresh(['user', 'representative']));
    }

    public function initiateCashPayment(Order $order, User $representative, int $amountTomans): string
    {
        $this->assertCanPay($order, $representative);

        $gateway = $this->split->usesSplitPlan($order)
            ? $this->split->cashGateway($order)
            : (string) $order->payment_method;

        $this->catalog->assertEnabled($gateway);

        $payment = $this->payments->createForOrder($order, $gateway, $representative->id, $amountTomans);
        $this->attachDarikMemberCodeIfNeeded($payment, $order->fresh('user'));

        $this->orderLog->system(
            $order,
            'نماینده '.$representative->name.' پرداخت نقدی '.number_format($amountTomans).' تومان را برای مشتری '.$order->user?->name.' آغاز کرد.',
            'rep_payment_cash_initiated'
        );

        return $this->payments->initiate($payment->fresh(), $order->fresh(['user', 'representative']));
    }

    public function initiateCreditPayment(Order $order, User $representative, int $amountTomans): string
    {
        $this->assertCanPay($order, $representative);

        $gateway = (string) $order->payment_method;
        $this->catalog->assertEnabled($gateway);

        $payment = $this->payments->createForOrder($order, $gateway, $representative->id, $amountTomans);
        $this->attachDarikMemberCodeIfNeeded($payment, $order->fresh('user'));

        $this->orderLog->system(
            $order,
            'نماینده '.$representative->name.' پرداخت اعتباری '.number_format($amountTomans).' تومان را برای مشتری '.$order->user?->name.' آغاز کرد.',
            'rep_payment_credit_initiated'
        );

        return $this->payments->initiate($payment->fresh(), $order->fresh(['user', 'representative']));
    }

    protected function attachDarikMemberCodeIfNeeded(Payment $payment, Order $order): void
    {
        if ($payment->gateway !== 'darik') {
            return;
        }

        $code = DarikMemberCode::normalize($order->user?->darik_member_code ?? '');
        if (! preg_match('/^5892\d{12}$/', $code)) {
            throw new RuntimeException('شناسه ۱۶ رقمی کیف پول داریک (شروع با 5892) برای مشتری ثبت نشده است.');
        }

        $existing = $payment->raw_response;
        $payload = is_array($existing) ? $existing : [];

        $payment->update([
            'raw_response' => array_merge($payload, [
                'darik' => ['member_code' => $code],
            ]),
        ]);
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
