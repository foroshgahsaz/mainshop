<?php

namespace App\Services\Payment;

use App\Models\Payment;

/**
 * ساخت URL صفحه پرداخت CPG (پارامترهای t و rt).
 * فرمت نوعی که API برمی‌گرداند: base/?t=TOKEN&rt=CALLBACK?tv=TOKEN
 */
final class NopayPaymentPageUrl
{
    public static function build(string $paymentPageBase, string $token, string $returnUrlBase): string
    {
        $token = trim($token);
        $base = rtrim(trim($paymentPageBase), '/');
        $returnUrlBase = rtrim(trim($returnUrlBase), '/');

        if ($token === '' || $base === '' || $returnUrlBase === '') {
            return '';
        }

        $rt = $returnUrlBase.'?tv='.rawurlencode($token);

        return $base.'/?t='.rawurlencode($token).'&rt='.rawurlencode($rt);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function resolve(string $apiRedirectUrl, string $token, array $config, Payment $payment): string
    {
        $override = trim((string) ($config['payment_page_base_url'] ?? ''));
        if ($override === '') {
            return trim($apiRedirectUrl);
        }

        $built = self::build(
            $override,
            $token,
            NopayReturnUrl::absolute($config, $payment),
        );

        return $built !== '' ? $built : trim($apiRedirectUrl);
    }
}