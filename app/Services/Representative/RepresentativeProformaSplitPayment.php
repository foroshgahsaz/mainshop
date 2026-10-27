<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayCatalog;

class RepresentativeProformaSplitPayment
{
    public function __construct(
        protected PaymentGatewayCatalog $catalog,
    ) {}

    public function usesSplitPlan(Order $order): bool
    {
        if (! $order->isProforma() || ! $order->isRepresentativeOrder()) {
            return false;
        }

        $credit = $order->proforma_planned_credit_amount;

        return $credit !== null
            && $credit > 0
            && $credit < (int) $order->final_amount;
    }

    public function plannedCreditAmount(Order $order): int
    {
        if (! $this->usesSplitPlan($order)) {
            return 0;
        }

        return (int) $order->proforma_planned_credit_amount;
    }

    public function plannedCashAmount(Order $order): int
    {
        if (! $this->usesSplitPlan($order)) {
            return 0;
        }

        return max(0, (int) $order->final_amount - $this->plannedCreditAmount($order));
    }

    public function paidCreditAmount(Order $order): int
    {
        return $this->sumSuccessfulPaymentsByType($order, PaymentGatewayCatalog::TYPE_CREDIT);
    }

    public function paidCashAmount(Order $order): int
    {
        return $this->sumSuccessfulPaymentsByType($order, PaymentGatewayCatalog::TYPE_CASH);
    }

    public function remainingCreditAmount(Order $order): int
    {
        if (! $this->usesSplitPlan($order)) {
            return 0;
        }

        return max(0, $this->plannedCreditAmount($order) - $this->paidCreditAmount($order));
    }

    public function remainingCashAmount(Order $order): int
    {
        if (! $this->usesSplitPlan($order)) {
            return max(0, $order->remainingAmount());
        }

        return max(0, $this->plannedCashAmount($order) - $this->paidCashAmount($order));
    }

    public function isSettlementComplete(Order $order): bool
    {
        if ((int) $order->final_amount <= 0) {
            return true;
        }

        if (! $this->usesSplitPlan($order)) {
            return $order->paidAmount() >= (int) $order->final_amount;
        }

        return $this->paidCreditAmount($order) >= $this->plannedCreditAmount($order)
            && $this->paidCashAmount($order) >= $this->plannedCashAmount($order);
    }

    public function cashGateway(Order $order): string
    {
        $configured = (string) ($order->proforma_cash_gateway ?? '');

        if ($configured !== '' && $this->catalog->isEnabled($configured)) {
            return $configured;
        }

        foreach ($this->catalog->cash() as $gateway) {
            return (string) $gateway['name'];
        }

        return 'parsian';
    }

    public function assertPlanAmounts(int $finalAmount, int $plannedCredit): void
    {
        if ($plannedCredit < 0 || $plannedCredit > $finalAmount) {
            throw new \InvalidArgumentException('مبلغ اعتباری باید بین ۰ و مبلغ نهایی پیش‌فاکتور باشد.');
        }
    }

    private function sumSuccessfulPaymentsByType(Order $order, string $type): int
    {
        $payments = $order->relationLoaded('payments')
            ? $order->payments
            : $order->payments()->get();

        $sum = 0;

        foreach ($payments as $payment) {
            if ($payment->status !== Payment::STATUS_SUCCESS) {
                continue;
            }

            $gatewayType = $this->catalog->definition((string) $payment->gateway)['type'] ?? PaymentGatewayCatalog::TYPE_CASH;

            if ($gatewayType === $type) {
                $sum += (int) $payment->amount;
            }
        }

        return $sum;
    }
}
