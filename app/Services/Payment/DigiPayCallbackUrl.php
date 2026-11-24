<?php

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * Build an absolute callback URL for DigiPay ticket creation.
 * Admin should store only the path; payment tracking code is always appended here.
 */
final class DigiPayCallbackUrl
{
    /**
     * @param  array<string, mixed>  $config  SettingsService::digipay()
     */
    public static function forPayment(Payment $payment, array $config): string
    {
        $callback = trim((string) ($config['callback_url'] ?? '/payment/callback/digipay'));

        if (str_contains($callback, '://')) {
            $parsed = parse_url($callback);
            $callback = $parsed['path'] ?? '/payment/callback/digipay';
            if (isset($parsed['query']) && is_string($parsed['query']) && $parsed['query'] !== '') {
                $callback .= '?'.$parsed['query'];
            }
        }

        if ($callback === '' || $callback === '/') {
            $callback = '/payment/callback/digipay';
        }

        if (! str_starts_with($callback, '/')) {
            $callback = '/'.$callback;
        }

        $callbackPath = $callback;
        $extraQuery = '';
        if (str_contains($callbackPath, '?')) {
            [$callbackPath, $extraQuery] = explode('?', $callbackPath, 2);
        }

        $callbackPath = rtrim($callbackPath, '/');
        if ($callbackPath === '') {
            $callbackPath = '/payment/callback/digipay';
        }

        $query = 'payment='.urlencode($payment->tracking_code);
        if ($extraQuery !== '') {
            $query .= '&'.$extraQuery;
        }

        return url($callbackPath.'?'.$query);
    }
}