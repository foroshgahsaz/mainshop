<?php

namespace App\Services\Payment;

use App\Models\Payment;
use RuntimeException;

/**
 * JetPay expects a full absolute returnUrl. If only a hostname or APP_URL=test is sent,
 * the portal may redirect to /fa/www.test.com?id=… instead of the merchant site.
 */
final class BajetReturnUrl
{
    /**
     * @param  array<string, mixed>  $config  SettingsService::bajet()
     */
    public static function forPayment(Payment $payment, array $config): string
    {
        $callback = trim((string) ($config['callback_url'] ?? '/payment/callback/bajet'));
        $base = trim((string) ($config['return_url_base'] ?? ''));

        if (str_contains($callback, '://')) {
            $parsed = parse_url($callback);
            if (is_array($parsed) && isset($parsed['host'])) {
                $scheme = $parsed['scheme'] ?? 'https';
                $base = $scheme.'://'.$parsed['host'].(isset($parsed['port']) ? ':'.$parsed['port'] : '');
                $callback = $parsed['path'] ?? '/payment/callback/bajet';
            }
        }

        if ($base === '') {
            $base = rtrim((string) config('app.url'), '/');
        } else {
            $base = rtrim($base, '/');
        }

        if ($callback === '' || $callback === '/') {
            $callback = '/payment/callback/bajet';
        }

        if (! str_starts_with($callback, '/')) {
            if (preg_match('#^[\w.-]+\.[a-z]{2,}#i', $callback)) {
                throw new RuntimeException(
                    'در تنظیمات باجت‌پی، «مسیر بازگشت» باید مثل /payment/callback/bajet باشد؛ دامنه را در «آدرس پایه سایت» (مثلاً https://www.chinibazar.ir) وارد کنید.'
                );
            }

            $callback = '/'.$callback;
        }

        return $base.$callback.'?payment='.urlencode($payment->tracking_code);
    }
}
