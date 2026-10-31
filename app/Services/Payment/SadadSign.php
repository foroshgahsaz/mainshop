<?php

namespace App\Services\Payment;

use RuntimeException;

/**
 * امضای Triple-DES (DES-EDE3) مطابق نمونه رسمی سداد (docs/PHP-master.zip).
 */
final class SadadSign
{
    public static function encryptPkcs7(string $plainText, string $base64TerminalKey): string
    {
        $key = base64_decode($base64TerminalKey, true);
        if ($key === false || $key === '') {
            throw new RuntimeException('کلید ترمینال سداد نامعتبر است.');
        }

        $cipher = openssl_encrypt($plainText, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
        if ($cipher === false) {
            throw new RuntimeException('خطا در تولید SignData سداد.');
        }

        return base64_encode($cipher);
    }

    public static function paymentSignData(string $terminalId, string $orderId, int $amountRial, string $base64TerminalKey): string
    {
        return self::encryptPkcs7("{$terminalId};{$orderId};{$amountRial}", $base64TerminalKey);
    }

    public static function verifySignData(string $token, string $base64TerminalKey): string
    {
        return self::encryptPkcs7($token, $base64TerminalKey);
    }
}
