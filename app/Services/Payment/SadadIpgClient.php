<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SadadIpgClient
{
    public const PAYMENT_REQUEST_URL = 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest';

    public const VERIFY_URL = 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify';

    public const PURCHASE_URL = 'https://sadad.shaparak.ir/VPG/Purchase';

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function paymentRequest(array $payload): array
    {
        return $this->postJson(self::PAYMENT_REQUEST_URL, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $token, string $signData): array
    {
        return $this->postJson(self::VERIFY_URL, [
            'Token' => $token,
            'SignData' => $signData,
        ]);
    }

    public function purchaseRedirectUrl(string $token): string
    {
        return self::PURCHASE_URL.'?Token='.urlencode($token);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function postJson(string $url, array $payload): array
    {
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->post($url, $payload);

        if ($response->failed()) {
            throw new RuntimeException('خطا در اتصال به درگاه سداد (HTTP '.$response->status().').');
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new RuntimeException('پاسخ نامعتبر از درگاه سداد.');
        }

        return $body;
    }
}
