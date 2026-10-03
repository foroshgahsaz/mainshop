<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentAuditLogger
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function step(Payment $payment, int $progress, string $code, string $message, array $context = []): void
    {
        $progress = max(0, min(100, $progress));
        $context = $this->sanitizeContext($context);

        $payload = [
            'payment_id' => $payment->id,
            'tracking_code' => $payment->tracking_code,
            'order_id' => $payment->order_id,
            'gateway' => $payment->gateway,
            'status' => $payment->status,
            'code' => $code,
            'progress' => $progress,
            'context' => $context,
        ];

        Log::channel('payments')->info("[{$progress}%] {$code}: {$message}", $payload);

        $this->paymentLog->system(
            $payment,
            "[{$progress}%] {$message}",
            'payment_audit',
            [
                'progress' => $progress,
                'code' => $code,
                'context' => $context,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function failure(Payment $payment, int $progress, string $code, string $message, ?Throwable $exception = null, array $context = []): void
    {
        $progress = max(0, min(100, $progress));

        if ($exception !== null) {
            $context['exception'] = $exception::class;
            $context['error'] = $exception->getMessage();
        }

        $this->step($payment, $progress, $code, $message, $context);

        if ($exception !== null) {
            Log::channel('payments')->error("[{$progress}%] {$code} failed: {$message}", [
                'payment_id' => $payment->id,
                'tracking_code' => $payment->tracking_code,
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return ['_note' => 'context could not be encoded'];
        }

        if (strlen($json) > 8000) {
            return ['_truncated' => true, 'preview' => mb_substr($json, 0, 4000)];
        }

        return $context;
    }
}
