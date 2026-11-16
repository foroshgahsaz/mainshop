<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NopayCpgClient
{
    public const SERVICE_REQUEST_TOKEN = 'CPG/Security/Token/RequestToken';

    public const SERVICE_VERIFY = 'CPG/Financial/VerifyTransaction';

    /**
     * @param  array<string, mixed>  $inputValue
     * @return array<string, mixed>
     */
    public function call(string $baseUrl, string $serviceName, array $inputValue): array
    {
        $url = rtrim($baseUrl, '/').'/';

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(45)
            ->post($url, [
                'InputValue' => $inputValue,
                'ServiceName' => $serviceName,
            ]);

        $contentType = (string) $response->header('Content-Type');

        if ($response->failed()) {
            $this->logBadResponse($url, $serviceName, $response->status(), $contentType, $response->body());

            throw new RuntimeException('خطا در اتصال به درگاه نوپی (HTTP '.$response->status().').');
        }

        $body = $this->decodeBody($response->body(), $response->json());
        if (! is_array($body)) {
            $preview = $this->bodyPreview($response->body());
            $this->logBadResponse($url, $serviceName, $response->status(), $contentType, $response->body());

            throw new RuntimeException(
                'پاسخ نامعتبر از درگاه نوپی (بدنه JSON نبود؛ احتمالاً آدرس API اشتباه یا پاسخ HTML/خالی).'
                .($preview !== '' ? ' نمونه: '.$preview : '')
            );
        }

        return $body;
    }

    /**
     * Wrapper نوپی گاهی آبجکت را به‌صورت رشته JSON (double-encoded) برمی‌گرداند.
     *
     * @param  mixed  $decoded
     * @return array<string, mixed>|null
     */
    public function decodeBody(string $rawBody, mixed $decoded = null): ?array
    {
        if ($decoded === null && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
        }

        for ($i = 0; $i < 3; $i++) {
            if (is_array($decoded)) {
                return $decoded;
            }

            if (! is_string($decoded)) {
                break;
            }

            $trimmed = trim($decoded);
            if ($trimmed === '' || (! str_starts_with($trimmed, '{') && ! str_starts_with($trimmed, '['))) {
                break;
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function hasErrors(array $response): bool
    {
        if ((bool) data_get($response, 'notification.hasErrors', false)) {
            return true;
        }

        return (bool) data_get($response, 'result.notification.hasErrors', false);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function firstErrorMessage(array $response): string
    {
        $errors = data_get($response, 'notification.errors', []);
        if (! is_array($errors) || $errors === []) {
            $errors = data_get($response, 'result.notification.errors', []);
        }

        if (is_array($errors) && isset($errors[0]['message'])) {
            $message = (string) $errors[0]['message'];
            $code = $errors[0]['code'] ?? null;
            if ($code !== null && $code !== '') {
                return $message.' (کد '.$code.')';
            }

            return $message;
        }

        return 'خطا در پاسخ درگاه نوپی.';
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>|null
     */
    public function entity(array $response): ?array
    {
        $entity = data_get($response, 'result.entity');

        return is_array($entity) ? $entity : null;
    }

    protected function logBadResponse(
        string $url,
        string $serviceName,
        int $status,
        string $contentType,
        string $rawBody,
    ): void {
        Log::warning('nopay.cpg.non_json_or_http_error', [
            'url' => $url,
            'service' => $serviceName,
            'http_status' => $status,
            'content_type' => $contentType !== '' ? $contentType : null,
            'body_preview' => $this->bodyPreview($rawBody),
            'body_length' => strlen($rawBody),
        ]);
    }

    protected function bodyPreview(string $rawBody): string
    {
        $trimmed = trim($rawBody);
        if ($trimmed === '') {
            return '(خالی)';
        }

        $oneLine = preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;

        return mb_substr($oneLine, 0, 180);
    }
}
