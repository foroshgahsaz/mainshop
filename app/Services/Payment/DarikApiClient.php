<?php

namespace App\Services\Payment;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DarikApiClient
{
    /** @param  array<string, mixed>  $config */
    public function loginToken(array $config, bool $forceRefresh = false): string
    {
        $cacheKey = $this->tokenCacheKey($config);

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        } else {
            Cache::forget($cacheKey);
        }

        $response = $this->http($config)
            ->withHeaders(['ApiKey' => (string) $config['api_key']])
            ->post('/api/v1/Login', [
                'Username' => (string) $config['username'],
                'Password' => (string) $config['password'],
            ]);

        $data = $this->json($response);
        $token = (string) data_get($data, 'token', '');
        $status = strtolower((string) data_get($data, 'Status', ''));

        if ($response->failed() || $token === '' || $status !== 'success') {
            throw new RuntimeException($this->errorMessage($data, 'ورود به API داریک ناموفق بود.'));
        }

        $ttl = 6900;
        $expiry = (string) data_get($data, 'expiry', '');
        if ($expiry !== '') {
            try {
                $expiresAt = Carbon::parse($expiry);
                $ttl = max(60, $expiresAt->diffInSeconds(now()) - 120);
            } catch (\Throwable) {
                // keep default ~115 min
            }
        }

        Cache::put($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $body
     * @return array{0: Response, 1: array<string, mixed>}
     */
    public function post(array $config, string $path, array $body): array
    {
        $response = null;
        $data = [];

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $token = $this->loginToken($config, $attempt > 1);

            $response = $this->http($config)
                ->withHeaders([
                    'ApiKey' => (string) $config['api_key'],
                    'Authorization' => 'Bearer '.$token,
                ])
                ->post($path, $body);

            $data = $this->json($response);

            if (! $this->isUnauthorized($response, $data)) {
                break;
            }
        }

        if ($response === null) {
            throw new RuntimeException('خطا در اتصال به داریک.');
        }

        return [$response, $data];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assertSuccess(Response $response, array $data, string $fallback): void
    {
        $status = strtolower((string) data_get($data, 'Status', data_get($data, 'status', '')));

        if ($response->failed() || ($status !== '' && $status !== 'success')) {
            throw new RuntimeException($this->errorMessage($data, $fallback));
        }
    }

    /** @param  array<string, mixed>  $config */
    protected function http(array $config): PendingRequest
    {
        return Http::baseUrl(rtrim((string) $config['base_url'], '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(45);
    }

  /**
     * @return array<string, mixed>
     */
    protected function json(Response $response): array
    {
        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function errorMessage(array $data, string $fallback): string
    {
        $message = trim((string) data_get($data, 'message', data_get($data, 'Message', '')));

        return $message !== '' ? $message : $fallback;
    }

    /** @param  array<string, mixed>  $config */
    protected function tokenCacheKey(array $config): string
    {
        return 'darik:api:token:'.sha1(
            (string) $config['base_url'].'|'.(string) $config['api_key'].'|'.(string) $config['username']
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function isUnauthorized(Response $response, array $data): bool
    {
        if ($response->status() === 401) {
            return true;
        }

        $code = (string) data_get($data, 'errorCode', '');

        return strtoupper($code) === 'INVALID_CREDENTIALS';
    }
}
