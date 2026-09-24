<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Collection;

class PaymentDeletionService
{
    /**
     * @return 'deleted'|'skipped'
     */
    public function delete(Payment $payment): string
    {
        if ($payment->status === Payment::STATUS_SUCCESS) {
            return 'skipped';
        }

        $payment->delete();

        return 'deleted';
    }

    /**
     * @param  Collection<int, Payment>|iterable<int, Payment>  $payments
     */
    public function deleteMany(iterable $payments): PaymentDeletionResult
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($payments as $payment) {
            if ($this->delete($payment) === 'skipped') {
                $skipped++;
            } else {
                $deleted++;
            }
        }

        return new PaymentDeletionResult($deleted, $skipped);
    }
}
