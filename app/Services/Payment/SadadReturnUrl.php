<?php

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * ReturnUrl سداد — OrderId (= payments.id) برای resolve کافی است؛ query اختیاری برای راحتی.
 */
final class SadadReturnUrl
{
    /**
     * @param  array<string, mixed>  $config  SettingsService::sadad()
     */
    public static function absolute(array $config, Payment $payment): string
    {
        $path = self::path($config);

        return url($path).'?payment='.$payment->tracking_code;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function path(array $config): string
    {
        $callback = trim((string) ($config['callback_url'] ?? '/payment/callback/sadad'));

        if (str_contains($callback, '://')) {
            $parsed = parse_url($callback);
            if (is_array($parsed) && isset($parsed['path'])) {
                $callback = $parsed['path'];
            }
        }

        if ($callback === '' || $callback === '/') {
            $callback = '/payment/callback/sadad';
        }

        if (! str_starts_with($callback, '/')) {
            $callback = '/'.$callback;
        }

        if (str_contains($callback, '?')) {
            $callback = strstr($callback, '?', true) ?: $callback;
        }

        return $callback;
    }
}
