<?php

namespace App\Services\Payment;

/**
 * JetPay/Bajet returns result.referUrl from the order API; the shop does not build that URL from scratch.
 * Some sandboxes return http on port 8443 or an internal host — normalize or rebuild using portal base URL from settings.
 */
final class BajetReferUrl
{
    /**
     * @param  array<string, mixed>  $config  SettingsService::bajet()
     */
    public static function resolve(string $referUrl, string $referenceId, array $config): string
    {
        $portal = self::portalBase($config);

        if ($portal !== '' && $referenceId !== '') {
            return $portal.'/fa/ep/invoice?requestId='.rawurlencode($referenceId);
        }

        return self::normalizeScheme($referUrl);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function portalBase(array $config): string
    {
        $sandbox = (bool) ($config['sandbox'] ?? true);
        $url = $sandbox
            ? (string) ($config['portal_sandbox_base_url'] ?? '')
            : (string) ($config['portal_base_url'] ?? '');

        return rtrim(trim($url), '/');
    }

    public static function normalizeScheme(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $port = $parts['port'] ?? null;

        if ($parts['scheme'] === 'http' && ($port === 8443 || $port === 443)) {
            $parts['scheme'] = 'https';

            return self::buildUrl($parts);
        }

        return $url;
    }

    /** @param  array<string, mixed>  $parts */
    private static function buildUrl(array $parts): string
    {
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}{$fragment}";
    }
}
