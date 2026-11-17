<?php

namespace App\Services\Payment;

use RuntimeException;

class NopayCpgPasswordEncryptor
{
    /**
     * رمز سرویس طبق مستند CPG Ver 2-5 و نمونه C# / AES-128-CBC:
     * publicKey = کلید ۱۶بایتی (base64)، privateKey = IV ۱۶بایتی (base64).
     * ارسال رمز خام باعث خطای احراز هویت (کد -7) می‌شود.
     */
    public function encryptServicePassword(string $plainPassword, string $publicKeyMaterial, string $privateKeyMaterial): string
    {
        $plainPassword = trim($plainPassword);
        if ($plainPassword === '') {
            throw new RuntimeException('رمز سرویس خالی است.');
        }

        $key = $this->decodeKeyMaterial($publicKeyMaterial, 'publicKey', 16);
        $iv = $this->decodeKeyMaterial($privateKeyMaterial, 'privateKey', 16);

        $encrypted = openssl_encrypt(
            $plainPassword,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($encrypted === false || $encrypted === '') {
            throw new RuntimeException(
                'رمزنگاری AES رمز سرویس ناموفق بود. مقدار رمزنگاری‌شده را از ابزار Encrypt نوپی در فیلد مربوط بگذارید.'
            );
        }

        return base64_encode($encrypted);
    }

    /**
     * اگر «رمز رمزنگاری‌شده» در ادمین پر باشد همان عیناً به درگاه می‌رود
     * (مقدار پشتیبانی نوپی). فقط وقتی encrypted خالی است از رمز متنی + AES استفاده می‌شود.
     *
     * @param  array<string, mixed>  $config
     */
    public function resolveEncryptedPassword(array $config): string
    {
        $preEncrypted = trim((string) ($config['service_password_encrypted'] ?? ''));
        if ($preEncrypted !== '') {
            return $preEncrypted;
        }

        $plain = trim((string) ($config['service_password'] ?? ''));
        if ($plain !== '') {
            return $this->encryptServicePassword(
                $plain,
                (string) ($config['public_key'] ?? ''),
                (string) ($config['private_key'] ?? ''),
            );
        }

        throw new RuntimeException('رمز سرویس نوپی تنظیم نشده است.');
    }

    protected function decodeKeyMaterial(string $material, string $label, int $expectedBytes): string
    {
        $material = preg_replace('/\s+/', '', trim($material)) ?? trim($material);
        if ($material === '') {
            throw new RuntimeException("کلید {$label} نوپی تنظیم نشده است.");
        }

        $decoded = base64_decode($material, true);
        if ($decoded === false || strlen($decoded) !== $expectedBytes) {
            throw new RuntimeException(
                "{$label} نوپی باید base64 معادل دقیقاً {$expectedBytes} بایت باشد (AES-128). مقدار فایل Encrypt/User information را کامل وارد کنید."
            );
        }

        return $decoded;
    }
}
