<?php

namespace App\Services\Payment;

/**
 * PEC tokens are large numeric strings; SOAP may expose them as int/float.
 */
final class PecToken
{
    public static function normalize(mixed $token): string
    {
        if ($token === null || $token === '') {
            return '';
        }

        if (is_int($token)) {
            return (string) $token;
        }

        if (is_float($token)) {
            return sprintf('%.0f', $token);
        }

        return trim((string) $token);
    }
}
