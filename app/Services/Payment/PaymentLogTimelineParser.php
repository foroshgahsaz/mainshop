<?php

namespace App\Services\Payment;

class PaymentLogTimelineParser
{
    /**
     * @param  list<string>  $lines
     * @return list<array{
     *     timestamp: string,
     *     progress: int,
     *     code: string,
     *     message: string,
     *     payment_id: int,
     *     tracking_code: ?string,
     *     order_id: ?int,
     *     gateway: ?string,
     *     status: ?string,
     *     context: array<string, mixed>,
     *     gateway_raw: mixed,
     *     has_gateway_payload: bool
     * }>
     */
    public function parseLines(array $lines): array
    {
        $events = [];

        foreach ($lines as $line) {
            $parsed = $this->parseLine($line);

            if ($parsed !== null) {
                $events[] = $parsed;
            }
        }

        usort($events, fn (array $a, array $b): int => [$a['timestamp'], $a['progress']] <=> [$b['timestamp'], $b['progress']]);

        return $events;
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return array<int, list<array<string, mixed>>>
     */
    public function groupByPaymentId(array $events): array
    {
        $groups = [];

        foreach ($events as $event) {
            $id = (int) ($event['payment_id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $groups[$id][] = $event;
        }

        krsort($groups);

        return $groups;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function parseLine(string $line): ?array
    {
        $line = trim($line);

        if ($line === '' || ! str_contains($line, 'payment_id')) {
            return null;
        }

        if (! preg_match('/^\[([^\]]+)\]/', $line, $timeMatch)) {
            return null;
        }

        $jsonStart = strrpos($line, '{');

        if ($jsonStart === false) {
            return null;
        }

        $payload = json_decode(substr($line, $jsonStart), true);

        if (! is_array($payload) || ! isset($payload['payment_id'])) {
            return null;
        }

        if (! preg_match('/\[(\d+)%\]\s+([^:]+):\s*(.+)\s+\{/', $line, $headMatch)) {
            return null;
        }

        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $gatewayRaw = $context['gateway_raw'] ?? null;
        $code = trim($headMatch[2]);
        $hasGatewayPayload = $gatewayRaw !== null || $this->isGatewayRelatedCode($code);

        return [
            'timestamp' => $timeMatch[1],
            'progress' => (int) ($payload['progress'] ?? (int) $headMatch[1]),
            'code' => $code,
            'message' => trim($headMatch[3]),
            'payment_id' => (int) $payload['payment_id'],
            'tracking_code' => isset($payload['tracking_code']) ? (string) $payload['tracking_code'] : null,
            'order_id' => isset($payload['order_id']) ? (int) $payload['order_id'] : null,
            'gateway' => isset($payload['gateway']) ? (string) $payload['gateway'] : null,
            'status' => isset($payload['status']) ? (string) $payload['status'] : null,
            'context' => $context,
            'gateway_raw' => $gatewayRaw,
            'has_gateway_payload' => $hasGatewayPayload,
        ];
    }

    public function formatGatewayPayloadForDisplay(mixed $gatewayRaw, array $context, string $code): string
    {
        if ($gatewayRaw !== null) {
            return $this->prettyJson($gatewayRaw);
        }

        if ($this->isGatewayRelatedCode($code) && $context !== []) {
            $withoutMeta = $context;
            unset($withoutMeta['gateway_raw']);

            if ($withoutMeta !== []) {
                return $this->prettyJson($withoutMeta);
            }
        }

        return '';
    }

    private function isGatewayRelatedCode(string $code): bool
    {
        return (bool) preg_match(
            '/^(parsian|zarinpal|digipay|tara|bajet|verify_gateway|callback_received|gateway_)/',
            $code
        );
    }

    private function prettyJson(mixed $data): string
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $encoded !== false ? $encoded : '';
    }
}
