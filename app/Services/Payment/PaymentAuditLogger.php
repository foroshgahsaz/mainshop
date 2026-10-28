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
    public function step(Payment $payment, int $progress, string $code, string $message, array $context = [], mixed $gatewayRaw = null): void
    {
        $progress = max(0, min(100, $progress));
        $context = $this->attachGatewayRaw($context, $gatewayRaw);
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
    public function failure(Payment $payment, int $progress, string $code, string $message, ?Throwable $exception = null, array $context = [], mixed $gatewayRaw = null): void
    {
        $progress = max(0, min(100, $progress));

        if ($exception !== null) {
            $context['exception'] = $exception::class;
            $context['error'] = $exception->getMessage();
        }

        $this->step($payment, $progress, $code, $message, $context, $gatewayRaw);

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
        $raw = $context['gateway_raw'] ?? null;
        unset($context['gateway_raw']);

        $json = json_encode($context, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $context = ['_note' => 'context could not be encoded'];
        } elseif (strlen($json) > 8000) {
            $context = ['_truncated' => true, 'preview' => mb_substr($json, 0, 4000)];
        }

        if ($raw !== null) {
            $context['gateway_raw'] = $this->sanitizeGatewayRaw($raw);
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function attachGatewayRaw(array $context, mixed $gatewayRaw): array
    {
        if ($gatewayRaw !== null) {
            $context['gateway_raw'] = $this->normalizeGatewayRaw($gatewayRaw);
        }

        return $context;
    }

    private function normalizeGatewayRaw(mixed $raw): mixed
    {
        if (is_scalar($raw) || $raw === null) {
            return $raw;
        }

        if (is_array($raw)) {
            return $raw;
        }

        if (is_object($raw)) {
            $encoded = json_encode($raw, JSON_UNESCAPED_UNICODE);

            if ($encoded !== false) {
                $decoded = json_decode($encoded, true);

                return is_array($decoded) ? $decoded : ['_value' => $encoded];
            }

            return ['_object' => $raw::class];
        }

        return ['_type' => gettype($raw)];
    }

    private function sanitizeGatewayRaw(mixed $raw): mixed
    {
        $normalized = $this->normalizeGatewayRaw($raw);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return ['_note' => 'gateway_raw could not be encoded'];
        }

        if (strlen($json) > 32000) {
            return [
                '_truncated' => true,
                'preview' => mb_substr($json, 0, 16000),
            ];
        }

        return $normalized;
    }
}
