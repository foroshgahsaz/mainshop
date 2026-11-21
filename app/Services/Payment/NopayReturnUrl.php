<?php

namespace App\Services\Payment;

use App\Models\Payment;

class NopayReturnUrl
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function absolute(array $config, Payment $payment): string
    {
        // CPG صفحه پرداخت پارامتر tv را با «?» به انتهای returnURL می‌چسباند؛ اگر returnURL
        // خودش query داشته باشد آدرس بازگشت خراب می‌شود: …?payment=X?tv=Y — شناسایی پرداخت از tv در callback.
        unset($payment);

        return url(self::pathWithoutQuery($config));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function path(array $config): string
    {
        return self::pathWithoutQuery($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function pathWithoutQuery(array $config): string
    {
        $callback = trim((string) ($config['callback_url'] ?? '/payment/callback/nopay'));
        if ($callback === '') {
            $callback = '/payment/callback/nopay';
        }

        if (str_starts_with($callback, 'http://') || str_starts_with($callback, 'https://')) {
            $parsed = parse_url($callback);

            return $parsed['path'] ?? '/payment/callback/nopay';
        }

        $path = str_starts_with($callback, '/') ? $callback : '/'.$callback;
        if (str_contains($path, '?')) {
            $path = explode('?', $path, 2)[0];
        }

        return $path !== '' ? $path : '/payment/callback/nopay';
    }
}