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
                $base = self::originFromParts($parsed);
                $callback = $parsed['path'] ?? '/payment/callback/bajet';
                if (isset($parsed['query']) && is_string($parsed['query']) && $parsed['query'] !== '') {
                    $callback .= '?'.$parsed['query'];
                }
            }
        }

        if ($base === '') {
            $base = (string) config('app.url');
        }

        $origin = self::normalizeOrigin($base);

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

        $callbackPath = $callback;
        $callbackQuery = '';
        if (str_contains($callbackPath, '?')) {
            [$callbackPath, $callbackQuery] = explode('?', $callbackPath, 2);
        }

        $url = $origin.rtrim($callbackPath, '/').'?payment='.urlencode($payment->tracking_code);
        if ($callbackQuery !== '') {
            $url .= '&'.$callbackQuery;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException(
                'آدرس بازگشت باجت‌پی معتبر نیست. در پنل ادمین، «آدرس پایه سایت» را با https کامل بگذارید (مثلاً https://www.chinibazar.ir).'
            );
        }

        return $url;
    }

    /** Normalize value saved in admin (auto-prefix https when user enters only hostname). */
    public static function normalizeStoredBase(string $base): string
    {
        $base = trim($base);
        if ($base === '') {
            return '';
        }

        return self::normalizeOrigin($base);
    }

    public static function normalizeOrigin(string $base): string
    {
        $base = trim($base);
        if ($base === '') {
            throw new RuntimeException('آدرس پایه سایت برای بازگشت از باجت‌پی خالی است.');
        }

        if (! preg_match('#^https?://#i', $base)) {
            if (preg_match('#^[\w.-]+\.[a-z]{2,}(?::\d+)?(?:/.*)?$#i', $base)) {
                $base = 'https://'.$base;
            } else {
                throw new RuntimeException(
                    'آدرس پایه سایت باید با https:// شروع شود (مثلاً https://www.chinibazar.ir).'
                );
            }
        }

        $parsed = parse_url($base);
        if (! is_array($parsed) || ! isset($parsed['host']) || $parsed['host'] === '') {
            throw new RuntimeException('آدرس پایه سایت برای باجت‌پی قابل تشخیص نیست.');
        }

        return rtrim(self::originFromParts($parsed), '/');
    }

    /** @param  array<string, mixed>  $parsed */
    protected static function originFromParts(array $parsed): string
    {
        $scheme = strtolower((string) ($parsed['scheme'] ?? 'https'));
        if ($scheme !== 'http' && $scheme !== 'https') {
            $scheme = 'https';
        }

        $host = (string) $parsed['host'];
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        return $scheme.'://'.$host.$port;
    }
}
