<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentNote;
use App\Models\User;
use App\Support\ShopLabels;

class PaymentActivityLogger
{
    public function system(Payment $payment, string $message, ?string $event = null, array $metadata = []): PaymentNote
    {
        return $payment->notes()->create([
            'user_id' => null,
            'type' => PaymentNote::TYPE_SYSTEM,
            'event' => $event,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }

    public function byUser(Payment $payment, User $user, string $message, string $type = PaymentNote::TYPE_PRIVATE): PaymentNote
    {
        return $payment->notes()->create([
            'user_id' => $user->id,
            'type' => $type,
            'message' => $message,
        ]);
    }

    public function created(Payment $payment): PaymentNote
    {
        return $this->system(
            $payment,
            sprintf(
                'درخواست پرداخت ایجاد شد. درگاه: %s. مبلغ: %s',
                $payment->gateway,
                ShopLabels::formatMoney($payment->amount)
            ),
            'payment_created',
            ['gateway' => $payment->gateway, 'amount' => $payment->amount]
        );
    }

    public function statusChanged(Payment $payment, string $from, string $to, ?string $detail = null): PaymentNote
    {
        $message = sprintf(
            'وضعیت پرداخت از «%s» به «%s» تغییر کرد.',
            ShopLabels::paymentStatus($from),
            ShopLabels::paymentStatus($to)
        );

        if ($detail) {
            $message .= ' '.$detail;
        }

        return $this->system($payment, $message, 'status_changed', [
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function gatewayResponse(Payment $payment, string $message, array $metadata = []): PaymentNote
    {
        return $this->system($payment, $message, 'gateway_response', $metadata);
    }
}
