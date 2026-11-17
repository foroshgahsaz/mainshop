<?php

namespace Tests\Unit;

use App\Services\Payment\NopayCpgPasswordEncryptor;
use RuntimeException;
use Tests\TestCase;

class NopayCpgPasswordEncryptorTest extends TestCase
{
    public function test_aes_128_cbc_encrypts_with_public_key_and_private_iv(): void
    {
        $publicKey = 'WtX3qPVc1lrHX/+ug9iILQ==';
        $privateKey = base64_encode(str_repeat('I', 16));

        $encrypted = app(NopayCpgPasswordEncryptor::class)
            ->encryptServicePassword('C3456', $publicKey, $privateKey);

        $raw = base64_decode($encrypted, true);
        $this->assertNotFalse($raw);
        $this->assertSame(16, strlen((string) $raw));

        $plain = openssl_decrypt(
            (string) $raw,
            'AES-128-CBC',
            base64_decode($publicKey, true),
            OPENSSL_RAW_DATA,
            base64_decode($privateKey, true),
        );

        $this->assertSame('C3456', $plain);
    }

    public function test_pre_encrypted_password_is_preferred_over_plain(): void
    {
        $resolved = app(NopayCpgPasswordEncryptor::class)->resolveEncryptedPassword([
            'service_password' => 'C3456',
            'service_password_encrypted' => 'from-nopay-support',
            'public_key' => 'WtX3qPVc1lrHX/+ug9iILQ==',
            'private_key' => base64_encode(str_repeat('V', 16)),
        ]);

        $this->assertSame('from-nopay-support', $resolved);
    }

    public function test_plain_used_only_when_encrypted_empty(): void
    {
        $publicKey = 'WtX3qPVc1lrHX/+ug9iILQ==';
        $privateKey = base64_encode(str_repeat('V', 16));

        $resolved = app(NopayCpgPasswordEncryptor::class)->resolveEncryptedPassword([
            'service_password' => 'C3456',
            'service_password_encrypted' => '',
            'public_key' => $publicKey,
            'private_key' => $privateKey,
        ]);

        $this->assertSame(
            app(NopayCpgPasswordEncryptor::class)->encryptServicePassword('C3456', $publicKey, $privateKey),
            $resolved,
        );
    }

    public function test_truncated_public_key_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('publicKey');

        app(NopayCpgPasswordEncryptor::class)->encryptServicePassword(
            'C3456',
            'WtX3q+ug9iILQ==',
            base64_encode(str_repeat('I', 16)),
        );
    }
}
