<?php

namespace App\Services\Payment;

use RuntimeException;

/**
 * PEC validates CallBackUrl against the merchant profile — must match exactly (no extra query params).
 * Payment is resolved on callback via OrderId (= payments.id).
 */
final class ParsianCallbackUrl
{
    /**
     * @param  array<string, mixed>  $config  SettingsService::parsian()
     */
    public static function absolute(array $config): string
    {
        $path = self::path($config);

        return rtrim((string) config('app.url'), '/').$path;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function path(array $config): string
    {
        $callback = trim((string) ($config['callback_url'] ?? '/payment/callback/pec'));

        if (str_contains($callback, '?')) {
            $callback = strstr($callback, '?', true) ?: $callback;
        }

        if (str_contains($callback, '://')) {
            $parsed = parse_url($callback);
            if (is_array($parsed) && isset($parsed['path'])) {
                $callback = $parsed['path'];
            }
        }

        if ($callback === '' || $callback === '/') {
            $callback = '/payment/callback/pec';
        }

        if (! str_starts_with($callback, '/')) {
            if (preg_match('#^[\w.-]+\.[a-z]{2,}#i', $callback)) {
                throw new RuntimeException(
                    'در تنظیمات پارسیان، Callback باید مسیر باشد (مثل /payment/callback/pec)؛ دامنه از APP_URL خوانده می‌شود.'
                );
            }

            $callback = '/'.$callback;
        }

        if ($callback === '/') {
            $callback = '/payment/callback/pec';
        }

        return $callback;
    }
}
