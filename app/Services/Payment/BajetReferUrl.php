<?php

namespace App\Services\Payment;

/**
 * JetPay/Bajet returns result.referUrl from the order API; the shop does not build that URL from scratch.
 * When portal base is configured, only the origin (scheme/host/port) is swapped — path and query stay as API returned.
 */
final class BajetReferUrl
{
    /**
     * @param  array<string, mixed>  $config  SettingsService::bajet()
     */
    public static function resolve(string $referUrl, string $referenceId, array $config): string
    {
        $referUrl = trim($referUrl);
        $portal = self::portalBase($config);

        if ($referUrl !== '') {
            $url = self::normalizeScheme($referUrl);

            if ($portal !== '') {
                $url = self::replaceOrigin($url, $portal);
            }

            if ($referenceId !== '') {
                $url = self::ensureReferenceInQuery($url, $referenceId);
            }

            return $url;
        }

        if ($portal !== '' && $referenceId !== '') {
            return $portal.'/fa/ep/login?id='.rawurlencode($referenceId);
        }

        return '';
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
            if ($port === 443) {
                unset($parts['port']);
            }

            return self::buildUrl($parts);
        }

        if ($parts['scheme'] === 'https' && $port === 443) {
            unset($parts['port']);

            return self::buildUrl($parts);
        }

        return $url;
    }

    public static function replaceOrigin(string $url, string $portalBase): string
    {
        $parts = parse_url($url);
        $portal = parse_url(rtrim(trim($portalBase), '/'));

        if ($parts === false || $portal === false || ! isset($parts['host'], $portal['host'])) {
            return $url;
        }

        $parts['scheme'] = strtolower((string) ($portal['scheme'] ?? 'https'));
        if ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https') {
            $parts['scheme'] = 'https';
        }

        $parts['host'] = (string) $portal['host'];
        $parts['port'] = $portal['port'] ?? null;

        return self::buildUrl($parts);
    }

    public static function ensureReferenceInQuery(string $url, string $referenceId): string
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        $query = [];
        if (isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== '') {
            parse_str($parts['query'], $query);
        }

        if (array_key_exists('requestId', $query) || array_key_exists('id', $query)) {
            if (array_key_exists('requestId', $query)) {
                $query['requestId'] = $referenceId;
            }
            if (array_key_exists('id', $query)) {
                $query['id'] = $referenceId;
            }
        } else {
            $query['id'] = $referenceId;
        }

        $parts['query'] = http_build_query($query);

        return self::buildUrl($parts);
    }

    /** @param  array<string, mixed>  $parts */
    private static function buildUrl(array $parts): string
    {
        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}{$fragment}";
    }
}
